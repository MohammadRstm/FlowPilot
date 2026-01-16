<?php

namespace App\Service\Copilot;

use App\Console\Commands\Services\IngestionService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetPoints{

    private static $N8N_CATALOG_COLLECTION = "n8n_catalog";
    private static $N8N_WORKFLOWS_COLLECTION = "n8n_workflows";
    private static $N8N_SCHEMAS_COLLECTION = "node_schemas";

    private static $numberOfRetrievedTriggerNodes = 3;
    private static $numberOfRetrievedActionNodes = 30;
    private static $numberOfRetrievedSchemasPerNode = 10;
    private static $numberOfRetrievedWorkflows = 30;

    public static function execute(array $analysis, ?callable $stage, ?callable $trace): array{
        $stage && $stage("retrieving");

        $densesVectors = self::getEmbeddingQueries($analysis);
        $sparseVectors = self::getSpareEmbeddings($analysis);

        $results = self::searchQdrant($densesVectors, $sparseVectors, $analysis);

        $trace && $trace("candidates", [
            "workflow_count" => count($analysis["nodes"] ?? []),
            "nodes" => $analysis["nodes"] ?? []
        ]);

        Log::info("Qdrant retrieval results counts", [
            "workflows" => count($results["workflows"] ?? []),
            "nodes"     => count($results["nodes"] ?? []),
            "schemas"   => count($results["schemas"] ?? [])
        ]);

        return [
            "workflows" => $results["workflows"],
            "nodes"     => $results["nodes"],
            "schemas"   => $results["schemas"]
        ];
    }

    private static function searchQdrant($densesVectors, $sparseVectors, $analysis){
        $workflows = self::searchWorkflows(
            $densesVectors["workflowDense"],
            $sparseVectors["workflowSparse"] ?? []
        );

        $nodes = self::searchNodes(
            $densesVectors["nodeDense"],
            $sparseVectors["nodeSparse"] ?? [],
        );

        $schemas = self::searchSchemas(
            $nodes
        );

        return [
            "workflows" => $workflows,
            "nodes"     => $nodes,
            "schemas"   => $schemas
        ];
    }

    /** WORKFLOW SEARCH */
    private static function searchWorkflows(array $dense, array $sparse): array{
        return self::query(
            self::$N8N_WORKFLOWS_COLLECTION,
            $dense,
            $sparse,
            null, 
            true,
            self::$numberOfRetrievedWorkflows
        );
    }

    /** NODES SEARCH */
    private static function searchNodes(array $dense, array $sparse): array{

        $triggerHits = self::getTriggerPoints($dense , $sparse);
        $actionNodesHits = self::getActionPoints($dense , $sparse);

        $hits = array_merge($triggerHits , $actionNodesHits);

        return $hits;
    }

    private static function getTriggerPoints(array $dense , array $sparse) : array | null{
        $filters = [
            "must" => [
                [
                    "key" => "node_type",
                    "match" => [
                        "value" => "trigger"
                    ]
                ]
            ]
        ];

       $triggerHits = self::query(
        self::$N8N_CATALOG_COLLECTION,
        $dense,
        $sparse,
        $filters,
        true,
        self::$numberOfRetrievedTriggerNodes
       );

       Log::info("Triggers found : " , ["context" => array_map(function($h){
            return[
                "hit" => $h["payload"]["class_name"]
            ];
        }, $triggerHits)]);

       return $triggerHits;
    }

    private static function getActionPoints(array $dense , array $sparse) : array | null{
        $filters = [
            "must_not" => [
                "key" => "node_type",
                "match" =>[
                    "value" => "trigger"
                ]
            ]
        ];

        $actionNodesHits = self::query(
            "n8n_catalog",
            $dense,
            $sparse,
            $filters,
            true,
            self::$numberOfRetrievedActionNodes
        );

        Log::info("searchNodes: retrieved hits", [
            "returned" => array_map(function($h){
                return[
                    "name" => $h["payload"]["class_name"]
                ];
            } , $actionNodesHits)
        ]);

        return $actionNodesHits;
    }

    /** SCHEMA SEARCH */
    // private static function searchSchemas(array $dense, array $sparse , array $nodeHits): array{
    //     // iterate over retrieved nodes and seach via node_id to node_normalized
    //     $schemas = [];
    //     foreach($nodeHits as $node){
    //         $filter = [
    //             "should" =>[
    //                 ["key" => "node_normalized" , "match" => ["value" => strtolower($node["payload"]["node_id"])]]
    //             ]
    //         ];

    //         $nodeSchema = self::query(
    //         "node_schemas",
    //         $dense,
    //         $sparse,
    //         $filter,
    //         true,
    //         self::$numberOfRetrievedSchemasPerNode
    //         );
            
    //         Log::info("Schemas for " . $node["payload"]["node_id"] . " :" , ["schema" => array_map(function($s){
    //             return[
    //                 "operation" => $s["payload"]["operation"] ?? "N/A"
    //             ];
    //         } , $nodeSchema)]);

    //         $schemas[] = $nodeSchema;
    //     }
       
    //     return $schemas;
    // }

    private static function searchSchemas(array $nodeHits): array{
        // iterate over retrieved nodes and seach via node_id to node_normalized
        $schemas = [];
        foreach($nodeHits as $node){
            $text = "".
                $node["payload"]["class_name"] . " ".
                $node["payload"]["node_id"] . " ".
                $node["payload"]["display_name"] . " ".
                $node["payload"]["description"];
             

            $denseVector = IngestionService::embed($text);
            $sparseVector = IngestionService::embed($text);

            $nodeSchema = self::query(
            self::$N8N_SCHEMAS_COLLECTION,
            $denseVector,
            $sparseVector,
            [],
            true,
            self::$numberOfRetrievedSchemasPerNode
            );
            
            Log::info("Schemas for " . $node["payload"]["node_id"] . " :" , ["schema" => array_map(function($s){
                return[
                    "name" => $s["payload"]["node_normalized"] ?? "N/A",
                    "operation" => $s["payload"]["operation"] ?? "N/A"
                ];
            } , $nodeSchema)]);

            $schemas[] = $nodeSchema;// an array of schemas that might relate to the node we searched for somehting like : 
            //Schemas for emailSend : {"schema":[{"name":"emailsend","operation":"send"},{"name":"awsses","operation":"send"},{"name":"awsses","operation":"sendTemplate"},{"name":"mailgun","operation":"default"},{"name":"mailchimp","operation":"send"},{"name":"awsses","operation":"send"},{"name":"mandrill","operation":"sendTemplate"},{"name":"mandrill","operation":"sendHtml"},{"name":"awsses","operation":"create"},{"name":"mailcheck","operation":"check"}]} 
        }
       
        return $schemas;
    }

    /** EMBEDDINGS */
    private static function getEmbeddingQueries($analysis){
        $workflowDense = IngestionService::embed(
            $analysis["embedding_query"] ?? ($analysis["intent"] ?? "")
        );

        $nodeDense = IngestionService::embed(
            self::buildNodeEmbeddingQuery($analysis)
        );

        return [
            "nodeDense" => $nodeDense,
            "workflowDense" => $workflowDense
        ];
    }

    private static function getSpareEmbeddings($analysis){
        $workflowSparse = IngestionService::buildSparseVector($analysis["intent"] ?? "");
        $nodeSparse = IngestionService::buildSparseVector(
            ($analysis["intent"] ?? "") . " " .
            implode(" ", $analysis["nodes"] ?? []) . " " .
            ($analysis["trigger"] ?? "")
        );

        return [
            "workflowSparse" => $workflowSparse,
            "nodeSparse" => $nodeSparse
        ];
    }

    private static function buildNodeEmbeddingQuery(array $analysis): string{
        $parts = [];

        if (!empty($analysis["trigger"])) {
            $parts[] = "n8n trigger " . $analysis["trigger"];
        }

        foreach ($analysis["nodes"] ?? [] as $n) {
            $parts[] = "n8n node " . $n;
        }

        if (!empty($analysis['intent'])) $parts[] = $analysis['intent'];

        return implode(" ", $parts);
    }


    private static function query(string $collection, ?array $dense, ?array $sparse, ?array $filters = [], mixed $includes = true, ?int $limit = 50): array{
        $endpoint = rtrim(env("QDRANT_CLUSTER_ENDPOINT", ''), '/');


        if (!$endpoint) {
            Log::error("QDRANT_CLUSTER_ENDPOINT is not configured");
            return [];
        }

        if (is_array($includes)) {
            $withPayload = ["include" => $includes];
        } else {
            $withPayload = $includes ?? true;
        }

        $payload = [
            "limit" => $limit ?? 50,
            "with_payload" => $withPayload,
            "vector" => [
                "name" => "dense-vector",
                "vector" => $dense ?? []
            ],
            "sparse_vector" => [
                "name" => "text-sparse",
                "vector" => $sparse ?? []
            ],
            "score_threshold" => 0.0
        ];

        if (!empty($filters)) {
            $payload["filter"] = $filters;
        }

        try {
            /** @var Response  */
            $response = Http::withHeaders([
                "api-key" => env("QDRANT_API_KEY")
            ])->post("{$endpoint}/collections/{$collection}/points/search", $payload);
        } catch (Exception $ex) {
            Log::error("Qdrant POST failed (network/exception)", [
                'collection' => $collection,
                'error' => $ex->getMessage()
            ]);
            return [];
        }

        if (!$response->ok()) {
            Log::error("Qdrant search failed for {$collection}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return [];
        }

        $result = $response->json("result") ?? [];
        return is_array($result) ? $result : [];
    }
}
