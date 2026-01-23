<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Log;

class RankingFlows {
    private static float $threshold = 0.75;
    private static int $workflowsToBeUsedIndex = 2; 

    public static function rank(array $analysis, array $points, ?callable $stage): array {
        $stage && $stage("ranking");

        $workflowScores = self::rankWorkflows($analysis, $points["workflows"] ?? []);
        $shouldReuse = self::shouldUseWorkflow($workflowScores);

        // pass nodes (node hits) too so we can leverage node_score provenance
        $rankedSchemas = self::rankSchemas(
            $analysis,
            $points["schemas"] ?? [],
            $points["nodes"] ?? []
        );

        $results = [
            "schemas" => $rankedSchemas,
        ];

        if ($shouldReuse) {
            $results["workflows"] = array_slice($workflowScores, 0, self::$workflowsToBeUsedIndex);
            Log::info('Reusing existing workflow', ['best_workflow_score' => $workflowScores[0]["score"] ?? null]);
            return $results;
        }

        return $results;
    }

    private static function shouldUseWorkflow($workflowScores) {
        $best = $workflowScores[0] ?? null;
        return $best && ($best["score"] ?? 0) > self::$threshold;
    }

    private static function rankWorkflows(array $analysis, array $hits): array {
        $scored = [];

        foreach ($hits as $hit) {
            $p = $hit["payload"] ?? [];

            $complexityScore = self::complexityScore($analysis["min_nodes"] ?? 0, count($p["nodes_used"] ?? []));

            $score = (($hit["score"] ?? 0) * 0.7) + ($complexityScore * 0.3);

            $scored[] = [
                "score" => round($score, 4),
                "workflow" => $p["workflow"] ?? null,
                "nodes" => $p["nodes_used"] ?? [],
                "raw" => $p["raw"] ?? null,
            ];
        }

        usort($scored, fn($a,$b) => $b["score"] <=> $a["score"]);
        return $scored;
    }

    private static function rankSchemas(
        array $analysis,
        array $schemaGroups,
        array $nodeHits
    ): array {
        // Build allowed node set from filtered nodes + triggers
        $allowedNodes = [];

        foreach ($nodeHits as $hit) {
            $p = $hit['payload'] ?? [];
            if (!empty($p['node_id'])) {
                $allowedNodes[strtolower($p['node_id'])] = true;
            }
        }

        Log::info("SchemaSelector: allowed nodes", [
            "allowed_nodes" => array_keys($allowedNodes),
        ]);

        $grouped = [];

        foreach ($schemaGroups as $groupIdx => $group) {
            if (!is_array($group)) continue;

            foreach ($group as $hit) {
                $schema = $hit['payload'] ?? null;
                if (!$schema) continue;

                $node = strtolower($schema['node_normalized'] ?? $schema['node'] ?? '');
                if ($node === '' || !isset($allowedNodes[$node])) {
                    Log::debug("Schema rejected: node not allowed", [
                        "node" => $schema['node_normalized'] ?? null,
                    ]);
                    continue;
                }

                $hasConfig =
                    !empty($schema['inputs']) ||
                    !empty($schema['fields']);

                $hasOutputs = !empty($schema['outputs']);

                if (!$hasConfig || !$hasOutputs) {
                    Log::debug("Schema rejected: not actionable", [
                        "node" => $node,
                        "has_config" => $hasConfig,
                        "has_outputs" => $hasOutputs,
                    ]);
                    continue;
                }

                $grouped[$node][] = $schema;

                Log::debug("Schema accepted", [
                    "node" => $node,
                    "resource" => $schema['resource'] ?? 'default',
                    "operation" => $schema['operation'] ?? 'default',
                ]);
            }
        }

        Log::info("SchemaSelector complete", [
            "nodes_with_schemas" => array_keys($grouped),
            "schema_count_per_node" => array_map('count', $grouped),
        ]);

        return $grouped;
    }

    private static function complexityScore(int $minRequired, int $actual): float {
        if ($actual === 0) return 0.0;
        if ($actual < $minRequired) return 0.0;

        if ($actual <= $minRequired * 1.5) return 1.0;
        if ($actual <= $minRequired * 2.5) return 0.8;
        return 0.6;
    }
}
