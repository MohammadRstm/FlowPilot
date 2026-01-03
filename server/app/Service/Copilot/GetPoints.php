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

    private static function buildNodeFilters(array $analysis): array {
        if (empty($analysis["nodes"])) {
            return [];
        }

        $variants = self::expandNodeNames($analysis["nodes"]);
        $classified = self::classifyVariants($variants, $analysis);

        $should = [];

        // node name
        if (!empty($classified["nodes"])) {
            $should[] = [
                "key" => "key_normalized",
                "match" => [ "any" => $classified["nodes"] ]
            ];
        }

        // service
        if (!empty($classified["services"])) {
            $should[] = [
                "key" => "service",
                "match" => [ "any" => $classified["services"] ]
            ];
        }

        // operation (read, write, send, trigger)
        if (!empty($classified["operations"])) {
            $should[] = [
                "key" => "operation",
                "match" => [ "any" => $classified["operations"] ]
            ];
        }

        return [ "should" => $should ];
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
        if (empty($analysis["nodes"])) {
            return [];
        }

        $variants = self::expandNodeNames($analysis["nodes"]);
        $classified = self::classifyVariants($variants, $analysis);

        $should = [];

        if (!empty($classified["nodes"])) {
            $should[] = [
                "key" => "node_normalized",
                "match" => [ "any" => $classified["nodes"] ]
            ];
        }

        if (!empty($classified["services"])) {
            $should[] = [
                "key" => "service",
                "match" => [ "any" => $classified["services"] ]
            ];
        }

        if (!empty($classified["operations"])) {
            $should[] = [
                "key" => "operation",
                "match" => [ "any" => $classified["operations"] ]
            ];
        }

        return [ "should" => $should ];
    }

    private static function expandNodeNames(array $nodes): array {
        $out = [];

        // canonical n8n vocabulary
        $serviceMap = [
            "gmail" => ["gmail", "email", "send", "message"],
            "email" => ["gmail", "email", "smtp"],
            "google" => ["google", "googleapi"],
            "sheet" => ["sheet", "sheets", "spreadsheet", "googlesheets"],
            "drive" => ["drive", "file", "upload", "download", "googledrive"],
            "cron" => ["cron", "schedule", "time", "trigger"],
            "webhook" => ["webhook", "http", "endpoint", "trigger"],
            "slack" => ["slack", "message", "post"],
            "http" => ["http", "request", "api", "web"]
        ];

        $functionMap = [
            "read" => ["read", "get", "fetch", "load"],
            "write" => ["write", "create", "insert", "add", "append"],
            "send" => ["send", "email", "notify", "message"],
            "trigger" => ["trigger", "start", "schedule", "cron"]
        ];

        foreach ($nodes as $raw) {
            $n = strtolower(trim($raw));
            $norm = preg_replace('/[^a-z0-9]/', '', $n);

            if (!$norm) continue;

            // always include raw + normalized
            $out[] = $n;
            $out[] = $norm;

            // Detect service family
            foreach ($serviceMap as $service => $aliases) {
                foreach ($aliases as $alias) {
                    if (str_contains($n, $alias)) {
                        $out[] = $service;
                        $out[] = "google" . $service;   // google + sheets, google + drive
                        $out[] = $service . "node";
                    }
                }
            }

            // detect function intent
            foreach ($functionMap as $fn => $verbs) {
                foreach ($verbs as $v) {
                    if (str_contains($n, $v)) {
                        $out[] = $fn;
                        $out[] = $fn . "node";
                        $out[] = $fn . "trigger";
                    }
                }
            }
        }

        return array_values(array_unique($out));
    }

    private static function classifyVariants(array $variants, array $analysis): array {
        $services = [];
        $nodes = [];

        foreach ($variants as $v) {
            if (str_starts_with($v, "google")) {
                $services[] = "google";
                $nodes[] = $v;
            }
            elseif (in_array($v, ["gmail","slack","http","cron","webhook"])) {
                $services[] = $v;
                $nodes[] = $v;
            }
            else {
                $nodes[] = $v;
            }
        }

        return [
            "services" => array_values(array_unique($services)),
            "nodes" => array_values(array_unique($nodes)),
            "operations" => self::extractOperations($analysis)
        ];
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

    private static function extractOperations(array $analysis): array {
        $text = strtolower(
            ($analysis["intent"] ?? "") . " " .
            implode(" ", $analysis["nodes"] ?? [])
        );

        $ops = [];

        if (preg_match('/read|get|fetch|load|list|lookup/', $text)) {
            $ops[] = "read";
        }
        if (preg_match('/write|create|insert|add|append|save|upload/', $text)) {
            $ops[] = "write";
        }
        if (preg_match('/send|email|notify|message/', $text)) {
            $ops[] = "send";
        }
        if (preg_match('/trigger|schedule|cron|every day|daily|webhook/', $text)) {
            $ops[] = "trigger";
        }

        return array_values(array_unique($ops));
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
