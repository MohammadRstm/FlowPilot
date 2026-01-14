<?php

namespace App\Service\Copilot;

use Exception;
use Illuminate\Support\Facades\Log;

class AnalyzeIntent{

    // Orchestrater
    public static function analyze(array $messages ,?callable $stage ,?callable $trace): array {
        $stage && $stage("analyzing");

        $final = LLMService::intentAnalyzer($messages);

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
