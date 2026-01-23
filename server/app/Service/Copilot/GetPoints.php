<?php

namespace App\Service\Copilot;

use App\Console\Commands\Services\IngestionService;
use Exception;
use Illuminate\Http\Client\Pool;
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

    private static float $minNodeScore = 0.15; 


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

        Log::info("Final points" , [
            "schemas"   => $schemas
        ]);

        return [
            "workflows" => $workflows,
            "nodes"     => $nodes,
            "schemas"   => $schemas
        ];
    }

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

        $actionNodesHits = self::filterByAdaptiveScore($actionNodesHits);

        Log::info("Action nodes after score filter", [
            "returned_after_filter" => array_map(function($h){
                return[
                    "name" => $h["payload"]["class_name"]
                ];
            } , $actionNodesHits)
        ]);

        $actionNodesHits = self::filterByAdaptiveScore($actionNodesHits);
        $actionNodesHits = self::keepLatestVersions($actionNodesHits);

        Log::info("Action nodes after version collapse", [
            "kept" => array_map(fn($h) => $h["payload"]["class_name"], $actionNodesHits)
        ]);

        return $actionNodesHits;
    }

    private static function filterByAdaptiveScore(array $hits, int $maxKeep = 8): array{
        if (empty($hits)) return [];

        usort($hits, function ($a, $b) {
            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });

        $best = $hits[0]['score'] ?? 0.0;

        if ($best <= 0.0) {
            Log::info("filterByAdaptiveScore: best score is zero, returning empty");
            return [];
        }

        if ($best >= 0.6) {
            $ratio = 0.35;
        } elseif ($best >= 0.4) {
            $ratio = 0.45;
        } elseif ($best >= 0.25) {
            $ratio = 0.60;
        } else {
            $selected = array_slice($hits, 0, 1);
            Log::info("filterByAdaptiveScore: best < 0.25, keeping top 1 only", [
                'best' => round($best, 3),
                'kept' => count($selected),
                'top_scores' => array_map(fn($h) => round($h['score'],3), array_slice($hits,0,5)),
            ]);
            return $selected;
        }

        $threshold = $best * $ratio;

        $kept = array_values(array_filter($hits, function ($hit) use ($threshold) {
            return ($hit['score'] ?? 0) >= $threshold;
        }));

        if (count($kept) > $maxKeep) {
            $kept = array_slice($kept, 0, $maxKeep);
        }

        Log::info("filterByAdaptiveScore", [
            'best' => round($best, 3),
            'ratio' => $ratio,
            'threshold' => round($threshold, 3),
            'kept' => count($kept),
            'scores_kept' => array_map(fn($h) => round($h['score'],3), $kept),
        ]);

        return $kept;
    }


    private static function searchSchemas(array $nodeHits): array{
        $nodesById = [];
        foreach ($nodeHits as $node) {
            $nodeId = $node['payload']['node_id'] ?? null;
            if (!$nodeId) continue;
            $nodesById[$nodeId] = $node;
        }

        $uniqueNodeIds = array_keys($nodesById);
        if (empty($uniqueNodeIds)) return [];

        $chunkSize = 8; 
        $batches = array_chunk($uniqueNodeIds, $chunkSize);

        $allSchemas = []; 

        foreach ($batches as $batch){   
            
            $responses = Http::pool(fn (Pool $pool) => array_map(function ($nodeId) use ($pool , $nodesById){
                $node = $nodesById[$nodeId];
                $schemasPerNode =  max(
                    3,
                    intval(self::$numberOfRetrievedSchemasPerNode * ($node['score'] ?? 0))
                );
                $text = trim(
                    ($node['payload']['class_name'] ?? '') . ' ' .
                    ($node['payload']['node_id'] ?? '') . ' ' .
                    ($node['payload']['display_name'] ?? '') . ' ' .
                    ($node['payload']['description'] ?? '')
                );

                $denseVector = IngestionService::embed($text);
                $sparseVector = IngestionService::buildSparseVector($text);

                $endpoint = rtrim(env("QDRANT_CLUSTER_ENDPOINT", ''), '/');
                $payload = [
                    'limit' => $schemasPerNode,
                    'with_payload' =>  true,
                    'vector' => ['name' => 'dense-vector', 'vector' => $denseVector ?? []],
                    'sparse_vector' => ['name' => 'text-sparse', 'vector' => $sparseVector ?? []],
                    'score_threshold' => 0.0,
                ];

                return $pool
                ->as($nodeId)
                ->withHeaders([
                    'api-key' => env('QDRANT_API_KEY'),
                ])
                ->post(
                    "{$endpoint}/collections/" . self::$N8N_SCHEMAS_COLLECTION . "/points/search",
                    $payload
                );
            }, $batch));

            foreach ($batch as $nodeId) {
                $resp = $responses[$nodeId];
                if (!$resp->ok()) {
                    Log::warning("Schema search failed for {$nodeId}", [
                            'status' => $resp->status(),
                            'body'   => $resp->body(),
                            'json'   => $resp->json(),
                            'headers'=> $resp->headers(),
                        ]);
                    $allSchemas[$nodeId] = [];
                    continue;
                }
                $result = $resp->json('result') ?? [];
                $allSchemas[$nodeId] = is_array($result) ? $result : [];
            }
        }

        $schemas = [];
        foreach ($nodeHits as $node) {
            $nodeId = $node['payload']['node_id'] ?? null;
            $schemas[] = $allSchemas[$nodeId] ?? [];
        }

        return $schemas;
    }

    private static function parseNodeVersion(string $className): array {
        if (preg_match('/^(.*?)(?:V(\d+))$/i', $className, $m)) {
            return [
                'base' => $m[1],
                'version' => (int) $m[2],
            ];
        }

        return [
            'base' => $className,
            'version' => 0, // legacy / unversioned
        ];
    }

    private static function keepLatestVersions(array $hits): array {
        $bestByBase = [];

        foreach ($hits as $hit) {
            $class = $hit['payload']['class_name'] ?? null;
            if (!$class) continue;

            ['base' => $base, 'version' => $version] = self::parseNodeVersion($class);

            if (!isset($bestByBase[$base])) {
                $bestByBase[$base] = $hit + ['__version' => $version];
                continue;
            }

            $current = $bestByBase[$base]['__version'];

            // Prefer higher version
            if ($version > $current) {
                $bestByBase[$base] = $hit + ['__version' => $version];
                continue;
            }

            // Same version → prefer higher score
            if ($version === $current && ($hit['score'] ?? 0) > ($bestByBase[$base]['score'] ?? 0)) {
                $bestByBase[$base] = $hit + ['__version' => $version];
            }
        }

        // Clean helper field
        return array_values(array_map(function ($h) {
            unset($h['__version']);
            return $h;
        }, $bestByBase));
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
