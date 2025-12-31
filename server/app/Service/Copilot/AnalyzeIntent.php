<?php

namespace App\Service\Copilot;

use Exception;
use Illuminate\Support\Facades\Log;

class AnalyzeIntent{
    // Orchestrater
    public static function analyze(string $question): array {
        $prompt = Prompts::getAnalysisPrompt($question);
        $raw = LLMService::raw($prompt);
        $parsed = self::parseResponse($raw);
        $parsed["nodes"] = self::normalizeNodes($parsed["nodes"] ?? []);
        $parsed["filters"] = self::buildFilters($parsed);// create filters for point retrieval
        $parsed["embedding_query"] = self::buildEmbeddingQuery($question, $parsed);

        Log::debug('Analyzed intent', ['analysis' => $parsed]);

        return $parsed;
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
        return array_values(array_unique(array_map(function ($n) {
            return preg_replace(
                '/[^a-z0-9]/',
                '',
                strtolower(trim($n))
            );
        }, $nodes)));
    }


    private static function buildFilters(array $analysis): array {// this is a general filter (used later in n8n_workflows filtering)
        $should = [];

        if (!empty($analysis["nodes"])) {
            $should[] = [
                "key" => "nodes_used",
                "match" => [
                    "any" => $analysis["nodes"]
                ]
            ];
        }

        if (!empty($analysis["category"])) {
            $should[] = [
                "key" => "category",
                "match" => [
                    "value" => $analysis["category"]
                ]
            ];
        }

        if (!empty($analysis["min_nodes"])) {
            $should[] = [
                "key" => "node_count",
                "range" => [
                    "gte" => intval($analysis["min_nodes"])
                ]
            ];
        }

        return ["should" => $should];
    }

    private static function buildEmbeddingQuery(string $question, array $analysis): string {
        return $analysis["intent"] . " | " . $question;
    }
}
