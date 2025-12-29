<?php

namespace App\Service\Copilot;

class RankingFlows{

    public static function rank(array $analysis, array $qdrantResults): array {
        $scored = [];

        foreach ($qdrantResults as $hit) {
            $payload = $hit["payload"];

            $score = self::scoreWorkflow($analysis, $hit["score"], $payload);

            $scored[] = [
                "score" => $score,
                "workflow" => $payload["workflow"],
                "nodes" => $payload["nodes_used"],
                "node_count" => $payload["node_count"],
                "raw" => $payload["raw"]
            ];
        }

        usort($scored, fn($a,$b) => $b["score"] <=> $a["score"]);

        return array_slice($scored, 0, 5);
    }

    private static function scoreWorkflow(array $analysis, float $vectorScore, array $payload): float {
        $score = 0;

        // 1. Qdrant semantic similarity (40%)
        $score += $vectorScore * 0.4;

        // 2. Node overlap (30%)
        $score += self::nodeMatchScore($analysis["nodes"], $payload["nodes_used"]) * 0.3;

        // 3. Intent match (20%)
        $score += self::intentScore($analysis["intent"], $payload["nodes_used"]) * 0.2;

        // 4. Complexity fit (10%)
        $score += self::complexityScore($analysis["complexity"], $payload["node_count"]) * 0.1;

        return round($score, 4);
    }

    private static function nodeMatchScore(array $wanted, array $has): float {
        if (count($wanted) === 0) return 0.5;

        $match = array_intersect($wanted, $has);

        return count($match) / count($wanted);
    }

    private static function intentScore(string $intent, array $nodes): float {
        $hasTrigger = collect($nodes)->some(fn($n) => str_contains(strtolower($n), "trigger"));

        if ($intent === "triggered" && $hasTrigger) return 1;
        if ($intent === "batch" && !$hasTrigger) return 1;

        return 0.4;
    }

    private static function complexityScore(string $complexity, int $count): float {
        return match($complexity){
            "simple" => $count <= 5 ? 1 : 0.3,
            "medium" => $count <= 12 ? 1 : 0.6,
            "complex" => $count >= 8 ? 1 : 0.7,
            default => 0.5
        };
    }




}
