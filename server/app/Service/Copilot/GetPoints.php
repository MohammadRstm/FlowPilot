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

        Log::info('Retrieved points from Qdrant', ['length_of_results_workflows' => count($results["workflows"]), 'length_of_results_nodes' => count($results["nodes"]), 'length_of_results_schemas' => count($results["schemas"])]);
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

    private static function buildNodeFilters(array $analysis): array
    {
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

    private static function buildSchemaFilters(array $analysis): array
    {
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


    private static function expandNodeNames(array $nodes): array
    {
        $expanded = [];

        foreach ($nodes as $node) {
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower($node));

            if (!$norm) continue;

            // base
            $expanded[] = $norm;

            // trigger variant
            if (!str_ends_with($norm, 'trigger')) {
                $expanded[] = $norm . 'trigger';
            }

            // non-trigger variant
            if (str_ends_with($norm, 'trigger')) {
                $expanded[] = substr($norm, 0, -7); // remove "trigger"
            }
        }

        return array_values(array_unique($expanded));
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
