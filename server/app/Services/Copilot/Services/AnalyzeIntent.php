<?php

namespace App\Services\Copilot\Services;

use App\Services\Copilot\Services\LLMService;

class AnalyzeIntent{

    public static function analyze(array $messages ,?callable $stage ,?callable $trace): array {
        $stage && $stage("analyzing");

        $final = LLMService::intentAnalyzer($messages);
        $final["embedding_query"] = self::buildWorkflowEmbeddingQuery($final,$final["question"]);
        $final["nodes"] = self::normalizeNodes($final["nodes"]);

        $trace && $trace("intent analysis", [
            "intent" => $final["intent"],
        ]);

        return $final;
    }

    public static function normalizeNodes(array $nodes): array {
        return array_values(array_unique(array_map(function ($n) {
            return preg_replace(
                '/[^a-z0-9]/',
                '',
                strtolower(trim($n))
            );
        }, $nodes)));
    }

    public static function normalizeNode(string $node): string {
        return preg_replace(
            '/[^a-z0-9]/',
            '',
            strtolower(trim($node))
        );
    }

    public static function buildWorkflowEmbeddingQuery(array $analysis, string $question): string {
        $parts = [];

        $parts[] = $analysis["intent"];

        if (!empty($analysis["trigger"])) {
            $parts[] = "Triggered by " . $analysis["trigger"];
        }

        if (!empty($analysis["nodes"])) {
            $parts[] = "Uses services: " . implode(", ", $analysis["nodes"]);
        }

        $parts[] = $question;

        return implode(". ", $parts);
    }

}
