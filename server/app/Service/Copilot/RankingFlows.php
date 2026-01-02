<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Log;

class RankingFlows{

    public static function rank(array $analysis, array $points): array {
        $workflowScores = self::rankWorkflows($analysis, $points["workflows"]);

        $best = $workflowScores[0] ?? null;
        
        // decide whether to reuse existing workflow or build new one
        if ($best && $best["score"] > 0.55) {
            $result = [
                "mode" => "reuse",
                "confidence" => $best["score"],
                "workflows" => array_slice($workflowScores, 0, 5)
            ];
            Log::info('Reusing existing workflow', ['best_workflow_score' => $best["score"]]);
            
            return $result;
        }
        // no workflow is good enough, give LLM building tools to build a new one
        $results = [
            "mode" => "build",
            "confidence" => $best["score"] ?? 0,
            "nodes" => self::rankNodes($analysis, $points["nodes"]),
            "schemas" => self::rankSchemas($analysis, $points["schemas"])
        ];
        Log::info('Building new workflow', ['best_existing_workflow_score' => $best["score"] ?? 0]);
        return $results;
    }

    private static function rankWorkflows(array $analysis, array $hits): array {
        $scored = [];

        foreach ($hits as $hit) {
            $p = $hit["payload"];

            $score =
                ($hit["score"] * 0.4) +
                (self::nodeMatchScore($analysis["nodes"], $p["nodes_used"]) * 0.3) +
                (self::intentScore($analysis["intent"], $p["nodes_uses"] ?? []) * 0.2) +
                (self::complexityScore($analysis["min_nodes"], $p["node_count"]) * 0.1);

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

    private static function nodeMatchScore(array $wanted, array $has): float {
        if (count($wanted) === 0) return 0.5;

        $match = array_intersect($wanted, $has);

        return count($match) / count($wanted);
    }

    private static function intentScore(string $intent, array $nodes): float {
        $hasTrigger = collect($nodes)->contains(fn($n) => str_contains(strtolower($n), "trigger"));

        if ($intent === "triggered" && $hasTrigger) return 1;
        if ($intent === "batch" && !$hasTrigger) return 1;

        return 0.4;
    }

   private static function complexityScore(int $minRequired, int $actual): float {
        if ($actual < $minRequired || $actual === 0) {
            return 0.0;
        }

        $ratio = $minRequired / $actual;

        return max(0.3, min(1.0, $ratio));
    }
}
