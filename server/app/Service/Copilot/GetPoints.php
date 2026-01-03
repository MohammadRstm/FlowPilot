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
            empty($analysis["nodes"]) ? "" : implode(" ", $analysis["nodes"])
        );


        return [
            "workflows" => self::searchWorkflows($workflowDense, $workflowSparse, $analysis),
            "nodes"     => self::searchNodes($nodeDense, $nodeSparse, $analysis),
            "schemas"   => self::searchSchemas($nodeDense, $nodeSparse, $analysis),
        ];
    }

    private static function searchWorkflows(array $dense, array $sparse, array $analysis): array {
        return self::query(
            "n8n_workflows",
            $dense,
            $sparse,
            [],
            50
        );
    }

    private static function searchNodes(array $dense, array $sparse, array $analysis): array {
        return self::query(
            "n8n_catalog",
            $dense,
            $sparse,
            self::buildNodeFilters($analysis),
            20
        );
    }

    private static function buildNodeFilters(array $analysis): array{
        if (empty($analysis["nodes"])) {
            return [];
        }

        $variants = self::expandNodeNames($analysis["nodes"]);

        return [
            "should" => [
                [
                    "key" => "key_normalized",
                    "match" => [
                        "any" => $variants
                    ]
                ]
            ]
        ];
    }

    private static function searchSchemas(array $dense, array $sparse, array $analysis): array {
        return self::query(
            "n8n_node_schemas",
            $dense,
            $sparse,
            self::buildSchemaFilters($analysis),
            50
        );
    }

    private static function buildSchemaFilters(array $analysis): array{
        if (empty($analysis["nodes"])) {
            return [];
        }

        $variants = self::expandNodeNames($analysis["nodes"]);

        return [
            "should" => [
                [
                    "key" => "node_normalized",
                    "match" => [
                        "any" => $variants
                    ]
                ]
            ]
        ];
    }

    private static function expandNodeNames(array $nodes): array {
        $expanded = [];

        foreach ($nodes as $node) {
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower($node));
            if (!$norm) continue;

            $expanded[] = $norm;

            // Common n8n patterns
            $expanded[] = $norm . "trigger";
            $expanded[] = $norm . "node";

            // Service family expansion (simple heuristic)
            if (str_contains($norm, 'google')) {
                $expanded[] = 'google';
            }
        }

        return array_values(array_unique($expanded));
    }

    private static function buildNodeEmbeddingQuery(array $analysis): string {
        if (empty($analysis["nodes"])) {
            return "";
        }

        return implode(
            " ",
            array_map(fn($n) => "n8n node " . $n, $analysis["nodes"])
        );
    }

    private static function query(string $collection, array $dense, array $sparse, ?array $filters, int $limit): array {
        $endpoint = rtrim(env("QDRANT_CLUSTER_ENDPOINT", ''), '/');

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders([
            "api-key" => env("QDRANT_API_KEY")
        ])->post("$endpoint/collections/$collection/points/search", [
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
        ]);

        if (!empty($filters)) {
            $payload["filter"] = $filters;
        }

        if (!$response->ok()) {
            throw new \Exception("Qdrant search failed for $collection: " . $response->body());
        }

        return $response->json("result");
    }
}
