<?php

namespace App\Service\Copilot;

use App\Console\Commands\Services\IngestionService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetPoints{

    public static function execute(array $analysis): array {
        $dense  = IngestionService::embed($analysis["embedding_query"]);
        $sparse = IngestionService::buildSparseVector($analysis["embedding_query"]);

        $results =  [
            "workflows" => self::searchWorkflows($dense, $sparse, $analysis),
            "nodes"     => self::searchNodes($dense, $sparse, $analysis),
            "schemas"   => self::searchSchemas($dense, $sparse, $analysis),
        ];

        Log::debug('Retrieved points from Qdrant', ['length_of_results_workflows' => count($results["workflows"]), 'length_of_results_nodes' => count($results["nodes"]), 'length_of_results_schemas' => count($results["schemas"])]);
        Log::info("Nodes retrieved: " . implode(", ", array_map(fn($n) => $n['payload']['node'] ?? 'unknown', $results["nodes"])));
        return $results;
    }

    private static function searchWorkflows(array $dense, array $sparse, array $analysis): array {
        return self::query(
            "n8n_workflows",
            $dense,
            $sparse,
            $analysis["filters"],
            30
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

    private static function buildNodeFilters(array $analysis): array {
        if (empty($analysis["nodes"])) {
            return [];
        }

        return [
            "should" => [
                [
                    "key" => "key_normalized",
                    "match" => [
                        "any" => array_map(
                            fn ($n) => preg_replace('/[^a-z0-9]/', '', strtolower($n)),
                            $analysis["nodes"]
                        )
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

    private static function buildSchemaFilters(array $analysis): array {
        if (empty($analysis["nodes"])) return [];

        return [
            "should" => [
                [
                    "key" => "node",
                    "match" => [
                        "any" => array_map("strtolower", $analysis["nodes"])
                    ]
                ]
            ]
        ];
    }

    private static function query(string $collection, array $dense, array $sparse, array $filters, int $limit): array {
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
            "filter" => $filters,
            "score_threshold" => 0.1
        ]);

        if (!$response->ok()) {
            throw new \Exception("Qdrant search failed for $collection: " . $response->body());
        }

        return $response->json("result");
    }
}
