<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Log;

class RankingFlows {
    private static float $threshold = 0.75;
    private static int $workflowsToBeUsedIndex = 2; 

    public static function rank(array $analysis, array $points, ?callable $stage): array {
        $stage && $stage("ranking");

        $workflowScores = self::rankWorkflows($analysis, $points["workflows"] ?? []); 
        $rankedSchemas = self::rankSchemas(
            $points["schemas"] ?? [],
            $points["nodes"] ?? []
        );
            
        $results = [
            "schemas" => $rankedSchemas,
        ];
                
        $shouldReuse = self::shouldUseWorkflow($workflowScores);
        if ($shouldReuse) {
            $results["workflows"] = self::getBestWorkflow($workflowScores);
            return $results;
        }

        return $results;
    }

    private static function getBestWorkflow(array $workflowScores){
        $bestWorkflow = array_slice($workflowScores, 0, self::$workflowsToBeUsedIndex);
        Log::info('Reusing existing workflow', ['best_workflow_score' => $workflowScores[0]["score"] ?? null]);
        return $bestWorkflow;
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

    private static function rankSchemas(array $schemaGroups,array $nodeHits): array {

        $allowedNodes = self::buildAllowedNodes($nodeHits);

        $grouped = [];

        foreach ($schemaGroups as $groupIdx => $group) {
            if (!is_array($group)) continue;

            self::groupSchemas($group , $allowedNodes , $grouped);
            
        }

        Log::info("SchemaSelector complete", [
            "nodes_with_schemas" => array_keys($grouped),
            "schema_count_per_node" => array_map('count', $grouped),
        ]);

        return $grouped;
    }

    private static function buildAllowedNodes(array $nodeHits){
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

        return $allowedNodes;
    }

    private static function groupSchemas(array $group , array $allowedNodes , array &$grouped){
        foreach ($group as $hit) {
            $schema = $hit['payload'] ?? null;
            if (!$schema) continue;

            $node = strtolower($schema['node_normalized'] ?? $schema['node'] ?? '');
            if ($node === '' || !isset($allowedNodes[$node])) {
                continue;
            }

            $hasConfig =
                !empty($schema['inputs']) ||
                !empty($schema['fields']);

            $hasOutputs = !empty($schema['outputs']);

            if (!$hasConfig || !$hasOutputs) {
                continue;
            }

            $grouped[$node][] = $schema;
        }
    }

    private static function complexityScore(int $minRequired, int $actual): float {
        if ($actual === 0) return 0.0;
        if ($actual < $minRequired) return 0.0;

        if ($actual <= $minRequired * 1.5) return 1.0;
        if ($actual <= $minRequired * 2.5) return 0.8;
        return 0.6;
    }
}
