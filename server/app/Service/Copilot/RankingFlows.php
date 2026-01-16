<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Log;

class RankingFlows{
    private static $threshold = 0.75;
    private static $workflowsToBeUsedIndex = 2;// choose top 3 workflows

    public static function rank(array $analysis, array $points , ?callable $stage): array{
        $stage && $stage("ranking");

        $workflowScores = self::rankWorkflows($analysis, $points["workflows"]);
        $shouldReuse = self::shouldUseWorkflow($workflowScores);

        $rankedSchemas =  self::rankSchemas($analysis, $points["schemas"]);

        $results = [
            "schemas" => $rankedSchemas
        ];

        if ($shouldReuse) {
            $result = [
                "workflows" => array_slice($workflowScores, 0, self::$workflowsToBeUsedIndex)
            ];
            Log::info('Reusing existing workflow', ['best_workflow_score' => $workflowScores[0]["score"]]);
            
            return $result;
        }
        return $results;
    }

    private static function shouldUseWorkflow($workflowScores){
        $best = $workflowScores[0] ?? null;

        return $best && $best["score"] > self::$threshold;
    }

    private static function rankWorkflows(array $analysis, array $hits): array {
        $scored = [];

        foreach($hits as $hit){
            $p = $hit["payload"];
    
            $complexityScore = self::complexityScore($analysis["min_nodes"]  , count($p["nodes_used"] ?? []));

            $score = 
                ($hit["score"] * 0.7) +       
                ($complexityScore * 0.3);


            $scored[] = [
                "score" => round($score, 4),
                "workflow" => $p["workflow"],
                "nodes" => $p["nodes_used"],
                "raw" => $p["raw"]
            ];
        }

        usort($scored, fn($a,$b) => $b["score"] <=> $a["score"]);
        return $scored;
    }

    private static function rankNodes(array $analysis, array $hits): array {
        $ranked = [];
        $requestedNodes = array_flip(
            AnalyzeIntent::normalizeNodes($analysis["nodes"])
        );

        foreach ($hits as $hit) {
            $p = $hit["payload"];

            $score = $hit["score"];

            $normalizedKey = AnalyzeIntent::normalizeNode($p["key"]);

            if (isset($requestedNodes[$normalizedKey])) {
                $score *= 1.4; // 40% boost
            }

            $ranked[] = [
                "score" => round($score, 4),
                "node" => $p["node"],
                "key" => $p["key"], 
                "categories" => $p["categories"],
            ];
        }

        usort($ranked, fn($a,$b) => $b["score"] <=> $a["score"]);

        $selected = [];
        $top = $ranked[0]["score"] ?? 0;

        foreach ($ranked as $node) {
            if ($node["score"] >= $top * 0.65) {
                $selected[] = $node;
            }
        }

        
        Log::info("Node ranking summary", [
            "requested_nodes" => $analysis["nodes"],
            "total_hits" => count($selected),
            "top_scores" => array_column(array_slice($selected, 0, 5), "score"),
        ]);
            
        Log::debug("Ranked nodes selected", [
            "count" => count($selected),
            "nodes" => array_map(fn ($n) => [
                "key" => $n["key"],
                "score" => $n["score"],
            ], $selected),
        ]);
        return $selected;
    }

private static function rankSchemas(array $analysis, array $hits): array {
    // helpers
    $normalize = function(string $s): string {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$s));
    };

    $tokenize = function(string $s): array {
        $splitCamel = preg_replace('/([a-z])([A-Z])/', '$1 $2', $s);
        $lower = strtolower($splitCamel);
        $parts = preg_split('/[^a-z0-9]+/', $lower, -1, PREG_SPLIT_NO_EMPTY);
        return $parts ?: [];
    };

    // prepare allowed terms from analysis
    $allowedRaw = $analysis['nodes'] ?? [];
    $allowedNodes = array_values(array_filter(array_map('trim', $allowedRaw)));
    $allowedMeta = [];
    foreach ($allowedNodes as $n) {
        $nstr = (string)$n;
        $allowedMeta[] = [
            'orig' => $nstr,
            'norm' => $normalize($nstr),
            'tokens' => $tokenize($nstr),
        ];
    }

    // Flatten nested hits (array of arrays). Keep origin group index for tracing.
    $flatHits = [];
    foreach ($hits as $groupIdx => $group) {
        // If this group itself is a list of hits (typical case)
        if (is_array($group) && isset($group[0]) && is_array($group[0])) {
            foreach ($group as $hit) {
                if (!is_array($hit)) continue;
                $hit['__origin_group'] = $groupIdx;
                $flatHits[] = $hit;
            }
        } elseif (is_array($group) && isset($group['payload'])) {
            // single hit provided directly (rare)
            $group['__origin_group'] = $groupIdx;
            $flatHits[] = $group;
        } else {
            // unknown shape: skip defensively
            continue;
        }
    }

    Log::debug("rankSchemas: flattened hits", [
        'groups' => count($hits),
        'flattened' => count($flatHits),
    ]);

    $ranked = [];

    foreach ($flatHits as $hit) {
        $payload = $hit['payload'] ?? [];
        if (!is_array($payload) || empty($payload)) continue;

        // Build the canonical text to match against (try multiple payload fields)
        $parts = [];
        foreach (['node_normalized', 'node', 'node_id', 'display_name', 'displayName', 'description', 'service'] as $f) {
            if (!empty($payload[$f]) && is_string($payload[$f])) $parts[] = $payload[$f];
        }
        $schemaCombined = trim(implode(' ', $parts));

        if ($schemaCombined === '') continue;

        $schemaNorm = $normalize($schemaCombined);
        $schemaTokens = $tokenize($schemaCombined);

        $qdrScore = isset($hit['score']) ? (float)$hit['score'] : (isset($payload['score']) ? (float)$payload['score'] : 0.0);

        // quick garbage discard (keep consistent with earlier behaviour)
        if ($qdrScore < 0.15) continue;

        // matching logic vs allowed nodes
        $matched = false;
        $matchReason = null;
        $boost = 1.0;

        foreach ($allowedMeta as $meta) {
            $allowedNorm = $meta['norm'];
            if ($allowedNorm === '') continue;

            // 1) substring checks (either side)
            if ($allowedNorm !== '' && (strpos($schemaNorm, $allowedNorm) !== false || strpos($allowedNorm, $schemaNorm) !== false)) {
                $matched = true;
                $matchReason = 'substring';
                $boost = max($boost, 1.6);
                break;
            }

            // 2) token intersection
            $inter = array_intersect($schemaTokens, $meta['tokens']);
            if (!empty($inter)) {
                $matched = true;
                $matchReason = 'token_intersection';
                $boost = max($boost, 1.4);
                break;
            }

            // 3) permissive levenshtein fallback (for short typos)
            $len = max(strlen($schemaNorm), strlen($allowedNorm));
            if ($len > 0) {
                $dist = levenshtein($schemaNorm, $allowedNorm);
                $threshold = max(3, (int)floor($len * 0.25));
                if ($dist <= $threshold) {
                    $matched = true;
                    $matchReason = 'levenshtein';
                    $boost = max($boost, 1.25);
                    break;
                }
            }
        }

        // If no textual match but qdrant score is very high, still include (recall)
        if (!$matched && $qdrScore >= 0.9) {
            $matched = true;
            $matchReason = 'high_similarity';
            $boost = max($boost, 1.1);
        }

        if (!$matched) continue;

        $finalScore = round($qdrScore * $boost, 4);

        $ranked[] = [
            'score' => $finalScore,
            'schema' => $payload,
            'raw_score' => $qdrScore,
            'match_reason' => $matchReason,
            'origin_group' => $hit['__origin_group'] ?? null,
        ];
    }

    // sort descending by score
    usort($ranked, fn($a, $b) => $b['score'] <=> $a['score']);

    // group by canonical schema node name (use normalized node or node field)
    $byNode = [];
    foreach ($ranked as $row) {
        $nodeKey = strtolower((string)($row['schema']['node_normalized'] ?? $row['schema']['node'] ?? $row['schema']['node_id'] ?? 'unknown'));
        if (!isset($byNode[$nodeKey])) $byNode[$nodeKey] = [];
        $byNode[$nodeKey][] = $row;
    }

    // enforce top-K per node
    $final = [];
    $maxPerNode = 3;
    foreach ($byNode as $nodeSchemas) {
        $final = array_merge($final, array_slice($nodeSchemas, 0, $maxPerNode));
    }

    // final sort
    usort($final, fn($a, $b) => $b['score'] <=> $a['score']);

    Log::info("Schemas selected for LLM", [
        "requested_nodes" => $allowedNodes,
        "groups_probed" => count($hits),
        "flattened_hits" => count($flatHits),
        "selected_count" => count($final),
        "sample" => array_map(fn($s) => [
            "node" => $s["schema"]["node"] ?? null,
            "node_normalized" => $s["schema"]["node_normalized"] ?? null,
            "op" => $s["schema"]["operation"] ?? null,
            "score" => $s["score"],
            "match" => $s["match_reason"] ?? null,
            "origin_group" => $s["origin_group"] ?? null,
        ], array_slice($final, 0, 10))
    ]);

    return $final;
}




    private static function complexityScore(int $minRequired, int $actual): float {
        if ($actual === 0) return 0.0;
        if ($actual < $minRequired) return 0.0;

        if ($actual <= $minRequired * 1.5) return 1.0;   
        if ($actual <= $minRequired * 2.5) return 0.8;   
        return 0.6;                                     
    }

}
