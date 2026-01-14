<?php

namespace App\Service\Copilot;

use App\Console\Commands\Services\IngestionService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetPoints{

    public static function execute(array $analysis ,?callable $stage ,?callable $trace): array {
        $stage && $stage("retrieving");

        $densesVectors = self::getEmbeddingQueries($analysis);
        $sparseVectors = self::getSpareEmbeddings($analysis);
  
        $results = self::searchQdrant($densesVectors , $sparseVectors , $analysis);

        $trace && $trace("candidates",[
            "workflow_count" => count($analysis["nodes"]),
            "nodes" => $analysis["nodes"]
        ]);

        Log::info("Nodes From Qdrant : " , ["nodes"=> $results["nodes"]]);

        return [
            "workflows" => $results["workflows"],
            "nodes"     => $results["nodes"],
            "schemas"   => $results["schemas"]
        ];
    }

    private static function searchQdrant($densesVectors , $sparseVectors , $analysis){
        $workflows =  self::searchWorkflows($densesVectors["worfklowDense"], $sparseVectors["workflowSpars"]);
        $nodes = self::searchNodes($densesVectors["nodeDense"], $sparseVectors["nodeSparse"], $analysis);
        $schemas = self::searchSchemas($densesVectors["nodeDense"], $sparseVectors["nodeSparse"]);

        return[
            "workflows" => $workflows,
            "nodes" => $nodes,
            "schemas" => $schemas
        ];
    }

    private static function getEmbeddingQueries($analysis){
        $workflowDense = IngestionService::embed(
           $analysis["embedding_query"]
        );

        $nodeDense = IngestionService::embed(
            self::buildNodeEmbeddingQuery($analysis)
        );

        return [
            "nodeDense" => $nodeDense,
            "worfklowDense" => $workflowDense
        ];
    }

    private static function getSpareEmbeddings($analysis){
        $workflowSparse = IngestionService::buildSparseVector($analysis["intent"]);
        $nodeSparse = IngestionService::buildSparseVector(
            $analysis["intent"] . " " .
            implode(" ", $analysis["nodes"] ?? []) . " " .
            ($analysis["trigger"] ?? "")
        );

        return [
            "workflowSpars" => $workflowSparse,
            "nodeSparse" => $nodeSparse
        ];
    }

    private static function searchWorkflows(array $dense, array $sparse): array {
        return self::query(
            "n8n_workflows",
            $dense,
            $sparse,
            [],
            null,
            30
        );
    }

    private static function searchNodes(array $dense, array $sparse): array {
        return self::query(
            "n8n_catalog",
            $dense,
            $sparse,
            [],
            [
                "node_id",
                "node",
                "key",
                "key_normalized",
                "categories"
            ],
            50
        );
    }

    private static function searchSchemas(array $dense, array $sparse): array {
        return self::query(
            "n8n_node_schemas",
            $dense,
            $sparse,
        );
    }

    private static function buildNodeEmbeddingQuery(array $analysis): string {
        $parts = [];

        if(!empty($analysis["trigger"])){
            $parts[] = "n8n trigger " . $analysis["trigger"];
        }

        foreach($analysis["nodes"] ?? [] as $n){
            $parts[] = "n8n node " . $n;
        }

        return implode(" ", $parts);
    }

    private static function query(string $collection, array $dense, array $sparse, ?array $filters = [], mixed $includes = true , ?int $limit = 50): array {
        $endpoint = rtrim(env("QDRANT_CLUSTER_ENDPOINT", ''), '/');

        if (is_array($includes)) {
            $withPayload = [
                "include" => $includes
            ];
        } else {
            $withPayload = $includes; // true or false
        }


        $payload = [
            "limit" => $limit,
            "with_payload" => $withPayload,
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
