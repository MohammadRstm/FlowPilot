<?php

namespace App\Service\Copilot;

use Exception;

class AnalyzeIntent{
    public static function analyze(string $question): array {
        $prompt = Prompts::getAnalysisPrompt($question);
        $raw = self::callLLM($prompt);
        $parsed = self::parseResponse($raw);
        $parsed["nodes"] = self::normalizeNodes($parsed["nodes"] ?? []);
        $parsed["filters"] = self::buildFilters($parsed);
        $parsed["embedding_query"] = self::buildEmbeddingQuery($question, $parsed);

        return $parsed;
    }

    private static function callLLM(string $prompt): string {
        return LLMService::raw($prompt);
    }

    private static function parseResponse(string $raw): array {
        $json = trim($raw);

        // Remove code blocks if present
        $json = preg_replace('/```(json)?/', '', $json);
        $json = trim($json);

        $data = json_decode($json, true);

        if (!$data) {
            throw new Exception("Invalid LLM response: " . $raw);
        }

        return $data;
    }

    private static function normalizeNodes(array $nodes): array {
        return array_values(array_unique(array_map(function($n){
            return ucfirst(strtolower(trim($n)));
        }, $nodes)));
    }

    private static function buildFilters(array $analysis): array {
        $must = [];

        if (!empty($analysis["nodes"])) {
            $must[] = [
                "key" => "nodes_used",
                "match" => [
                    "any" => $analysis["nodes"]
                ]
            ];
        }

        if (!empty($analysis["category"])) {
            $must[] = [
                "key" => "category",
                "match" => [
                    "value" => $analysis["category"]
                ]
            ];
        }

        if (!empty($analysis["min_nodes"])) {
            $must[] = [
                "key" => "node_count",
                "range" => [
                    "gte" => intval($analysis["min_nodes"])
                ]
            ];
        }

        return ["must" => $must];
    }

    private static function buildEmbeddingQuery(string $question, array $analysis): string {
        return $analysis["intent"] . " | " . $question;
    }
}
