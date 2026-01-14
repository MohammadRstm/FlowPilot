<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Log;

class RankingFlows{

    public static function rank(array $analysis, array $points , ?callable $stage): array{
        $stage && $stage("ranking");

        $workflowScores = self::rankWorkflows($analysis, $points["workflows"]);
        $best = $workflowScores[0] ?? null;

        $shouldReuse =
            $best &&
            $best["score"] > 0.5;

        $results = [
            "nodes" => self::rankNodes($analysis, $points["nodes"]),
            "schemas" => self::rankSchemas($analysis, $points["schemas"])
        ];

        if ($shouldReuse) {
            $result = [
                "workflows" => array_slice($workflowScores, 0, 5)
            ];
            Log::info('Reusing existing workflow', ['best_workflow_score' => $best["score"]]);
            
            return $result;
        }
        return $results;
    }

    private static function rankWorkflows(array $analysis, array $hits): array {
        $scored = [];

        foreach($hits as $hit){
            $p = $hit["payload"];
    
            $intentScore = self::intentScore($analysis["intent"]  , $p["nodes_used"] ?? []);
            $complexityScore = self::complexityScore($analysis["min_nodes"]  , count($p["nodes_used"] ?? []));

            $score = 
                ($hit["score"] * 0.7) +       
                // ($intentScore * 0.2) +
                ($complexityScore * 0.1);


            $scored[] = [
                "score" => round($score, 4),
                "workflow" => $p["workflow"],
                "nodes" => $p["nodes_used"],
                "raw" => $p["raw"]
            ];

            Log::debug("Workflow score", [
                "qdrant" => $hit["score"],
                "intent" => $intentScore,
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

            if (in_array(strtolower($p["key"]), array_map("strtolower",$analysis["nodes"]))) {
                $score += 0.5; // strong boost for explicitly requested nodes
            }

            $ranked[] = [
                "score" => round($score, 4),
                "node" => $p["node"],
                "key" => $p["key"],
                "categories" => $p["categories"],
                "docs" => $p["docs"],
                "credentials" => $p["credentials"]
            ];
        }

        usort($ranked, fn($a,$b) => $b["score"] <=> $a["score"]);

        return array_slice($ranked, 0, 15);
    }

    private static function rankSchemas(array $analysis, array $hits): array {
        $ranked = [];

        foreach ($hits as $hit) {
            $p = $hit["payload"];

            $score = $hit["score"];

            if (in_array(strtolower($p["node"]), array_map("strtolower",$analysis["nodes"]))) {
                $score += 0.4;
            }

            $ranked[] = [
                "score" => round($score, 4),
                "node" => $p["node"],
                "resource" => $p["resource"],
                "operation" => $p["operation"],
                "fields" => $p["fields"]
            ];
        }

        usort($ranked, fn($a,$b) => $b["score"] <=> $a["score"]);

        return array_slice($ranked, 0, 30);
    }


    // needs modification
    private static function intentScore(string $intent, array $nodes): float {
        $hasTrigger = collect($nodes)->contains(fn($n) => str_contains(strtolower($n), "trigger"));
        return match ($intent) {
            "triggered" => $hasTrigger ? 1.0 : 0.6,
            "batch"     => $hasTrigger ? 0.6 : 1.0,
            default     => 0.5,
        };
    }


    private static function complexityScore(int $minRequired, int $actual): float {
        if ($actual === 0) return 0.0;
        if ($actual < $minRequired) return 0.0;

        if ($actual <= $minRequired * 1.5) return 1.0;   
        if ($actual <= $minRequired * 2.5) return 0.8;   
        return 0.6;                                     
    }

}
