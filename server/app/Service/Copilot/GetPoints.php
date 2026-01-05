<?php

namespace App\Service\Copilot;

use App\Console\Commands\Services\IngestionService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetPoints{

    public static function execute(array $analysis): array {
        $workflowDense = IngestionService::embed(
           $analysis["embedding_query"]
        );

        $nodeDense = IngestionService::embed(
            self::buildNodeEmbeddingQuery($analysis)
        );

        $workflowSparse = IngestionService::buildSparseVector($analysis["intent"]);
        $nodeSparse = IngestionService::buildSparseVector(
            $analysis["intent"] . " " .
            implode(" ", $analysis["nodes"] ?? []) . " " .
            ($analysis["trigger"] ?? "")
        );

        return [
            "workflows" => self::searchWorkflows($workflowDense, $workflowSparse, $analysis),
            "nodes"     => self::searchNodes($nodeDense, $nodeSparse, $analysis),
            "schemas"   => self::searchSchemas($nodeDense, $nodeSparse, $analysis),
        ];
    }

    private static function searchWorkflows(array $dense, array $sparse): array {
        return self::query(
            "n8n_workflows",
            $dense,
            $sparse,
            [],
            50
        );
    }

    private static function searchNodes(array $dense, array $sparse): array {
        return self::query(
            "n8n_catalog",
            $dense,
            $sparse,
            [],
            30
        );
    }

    private static function searchSchemas(array $dense, array $sparse): array {
        return self::query(
            "n8n_node_schemas",
            $dense,
            $sparse,
            [],
            50
        );
    }

    private static function buildNodeEmbeddingQuery(array $analysis): string {
        $parts = [];

        if (!empty($analysis["trigger"])) {
            $parts[] = "n8n trigger " . $analysis["trigger"];
        }

        foreach ($analysis["nodes"] ?? [] as $n) {
            $parts[] = "n8n node " . $n;
        }

        return implode(" ", $parts);
    }

    private static function query(string $collection, array $dense, array $sparse, ?array $filters, int $limit): array {
        $endpoint = rtrim(env("QDRANT_CLUSTER_ENDPOINT", ''), '/');

        $payload = [
            "limit" => $limit,
            "with_payload" => true,
            "vector" => [
                "name" => "dense-vector",
                "vector" => $dense
            ],
            "sparse_vector" => [
                "name" => "text-sparse",
                "vector" => $sparse
            ],
            "score_threshold" => 0.1
        ];

        if (!empty($filters)) {
            $payload["filter"] = $filters;
        }

        /** @var Response */
        $response = Http::withHeaders([
            "api-key" => env("QDRANT_API_KEY")
        ])->post("$endpoint/collections/$collection/points/search", $payload);

        if (!$response->ok()) {
            throw new \Exception("Qdrant search failed for $collection: " . $response->body());
        }

        return $response->json("result");
    }
}
