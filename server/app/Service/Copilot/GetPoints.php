<?php

namespace App\Service\Copilot;

use Exception;
use Illuminate\Support\Facades\Http;

class GetPoints
{
    public static function execute(array $analysis): array {
        $dense = self::embed($analysis["embedding_query"]);
        $sparse = self::buildSparseVector($analysis["embedding_query"]);

        return self::queryQdrant($dense, $sparse, $analysis["filters"]);
    }

    private static function buildSparseVector(string $text): array {
        $text = strtolower($text);
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);

        $tokens = preg_split('/[^a-z0-9]+/i', $text);

        $freqs = [];

        foreach ($tokens as $token) {
            if (strlen($token) < 2) continue;
            $freqs[$token] = ($freqs[$token] ?? 0) + 1;
        }

        $indices = [];
        $values  = [];

        foreach ($freqs as $token => $count) {
            $indices[] = crc32($token);
            $values[]  = (float) $count;
        }

        return [
            "indices" => $indices,
            "values"  => $values
        ];
    }

    private static function embed(string $text): array {
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->post("https://api.openai.com/v1/embeddings", [
                "model" => "text-embedding-3-large",
                "input" => $text
            ]);

        if (!$response->ok()) {
            throw new Exception("Embedding failed");
        }

        return $response->json("data.0.embedding");
    }

    private static function queryQdrant(array $dense, array $sparse, array $filters): array {
        $endpoint = rtrim(env("QDRANT_URL"), '/');

        $response = Http::withHeaders([
            "api-key" => env("QDRANT_API_KEY")
        ])->post($endpoint . "/collections/n8n_workflows/points/search", [
            "limit" => 30,
            "with_payload" => true,
            "vector" => [
                "name" => "dense-vector",
                "vector" => $dense
            ],
            "sparse_vector" => [
                "name" => "text-sparse",
                "vector" => $sparse
            ],
            "filter" => $filters,
            "score_threshold" => 0.15
        ]);

        if (!$response->ok()) {
            throw new Exception("Qdrant search failed: " . $response->body());
        }

        return $response->json("result");
    }

    







}
