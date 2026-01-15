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

        $rankedNodes =  self::rankNodes($analysis, $points["nodes"]); 
        $rankedSchemas =  self::rankSchemas($analysis, $points["schemas"] , $rankedNodes);

        $results = [
            "nodes" => $rankedNodes,
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

            Log::debug("Workflow score", [
                "qdrant" => $hit["score"],
                "complexity" => $complexityScore,
                "final" => $score
            ]);
        }

        usort($scored, fn($a,$b) => $b["score"] <=> $a["score"]);
        return $scored;
    }

    private static function rankNodes(array $analysis, array $hits): array {
        $ranked = [];

        foreach ($hits as $hit) {
            $p = $hit["payload"];

            $score = $hit["score"];

            if (in_array(AnalyzeIntent::normalizeNodes($p["key"]), array_map("strtolower",$analysis["nodes"]))){
                $score *= 1.4; // 40% boost for explicitly requested nodes
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

   private static function rankSchemas(array $analysis, array $hits, array $nodes): array {
        $allowedNodes = array_map(
            fn($n) => strtolower($n["key"]),
            $nodes
        );

        $filtered = array_filter($hits, function ($hit) use ($allowedNodes) {
            $node = strtolower($hit["payload"]["node"]);
            return in_array($node, $allowedNodes);
        });

        $ranked = [];

        foreach ($filtered as $hit) {
            $p = $hit["payload"];
            $score = $hit["score"]; // qdrant similarity

            // 40% boost if node explicitly requested by user
            if (in_array(
                AnalyzeIntent::normalizeNodes($p["node_normalized"]),
                array_map("strtolower", $analysis["nodes"])
            )){
                $score *= 1.4;
            }

            // discard garbage matches early
            if ($score < 0.15) {
                continue;
            }

            $ranked[] = [
                "score"  => round($score, 4),
                "schema" => $p
            ];
        }

        usort($ranked, fn($a, $b) => $b["score"] <=> $a["score"]);

        // group by node 
        $byNode = [];
        foreach ($ranked as $row) {
            $node = strtolower($row["schema"]["node"]);
            $byNode[$node][] = $row;
        }

        // enforce top-K per node (prevents hallucination)
        $final = [];
        foreach ($byNode as $nodeSchemas) {
            $final = array_merge($final, array_slice($nodeSchemas, 0, 3)); // max 3 ops per node
        }

        usort($final, fn($a, $b) => $b["score"] <=> $a["score"]);

        Log::info("Schemas selected for LLM", [
            "nodes" => array_keys($byNode),
            "count" => count($final),
            "top"   => array_map(fn($s) => [
                "node" => $s["schema"]["node"],
                "op"   => $s["schema"]["operation"] ?? null,
                "score"=> $s["score"]
            ], array_slice($final, 0, 5))
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
