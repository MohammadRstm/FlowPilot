<?php

namespace App\Service\Copilot;

use Exception;
use Illuminate\Support\Facades\Log;

class AnalyzeIntent{

    // Orchestrater
    public static function analyze(array $question): array {
        $intentData = LLMService::intentAnalyzer($question);
        Log::debug("intent data" , ["intent data" => $intentData]);
        $nodeData = LLMService::nodeAnalyzer($intentData["question"], $intentData["intent"]);
        Log::debug("node Data " , ["node data" => $nodeData]);
        $final = LLMService::workflowSchemaValidator($intentData, $nodeData);
        Log::debug("final Data " , ["final" => $final]);

        $final["intent"] = $intentData["intent"];
        $final["trigger"] = $intentData["trigger"];
        $final["question"] = $intentData["question"];
        $final["nodes"] = self::normalizeNodes($final["nodes"]);
        $final["embedding_query"] = self::buildWorkflowEmbeddingQuery($final, $intentData["question"]);

        Log::info("Intent" , ["intent" => $final["intent"]]);
        Log::info("embedding_query" , ["embedding" => $final["embedding_query"]]);

        return $final;
    }

    private static function normalizeNodes(array $nodes): array {
        return array_values(array_unique(array_map(function ($n) {
            return preg_replace(
                '/[^a-z0-9]/',
                '',
                strtolower(trim($n))
            );
        }, $nodes)));
    }


    private static function buildWorkflowEmbeddingQuery(array $analysis, string $question): string {
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
