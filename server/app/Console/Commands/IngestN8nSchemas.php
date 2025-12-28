<?php

namespace App\Console\Commands;

use App\Console\Commands\Services\IngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;


class IngestN8nSchemas extends Command{

    protected $signature = 'app:ingest-n8n-schemas';
    protected $description = 'Extract n8n node schemas from TypeScript and store in Qdrant';

    public function handle(){
        
        /** @var Response $response */
        $response = Http::withHeaders([
            'User-Agent' => 'Laravel-RAG',
            'Authorization' => 'token ' . "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"
        ])->get(
            'https://api.github.com/repos/n8n-io/n8n/contents/packages/nodes-base/nodes'
        );

        if(!$response->ok()){
            $this->error('GitHub API failed. Status: ' . $response->status());
            $this->error('Response body: ' . $response->body());
            return;
        }

        /** @var array|null $nodes */
        $nodes = $response->json();

        foreach($nodes as $node){
            if ($node['type'] !== 'dir') continue;

            $this->info("Parsing {$node['name']}");

            /** @var Response $response */
            $response = Http::withHeaders([
                'User-Agent' => 'Laravel-RAG',
                'Authorization' => 'token ' . "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"
            ])->get($node['url']);

            if(!$response->ok()){
                $this->error('GitHub API failed. Status: ' . $response->status());
                $this->error('Response body: ' . $response->body());
                return;
            }

            /** @var array|null $files */
            $files = $response->json();

            $ts = '';

            foreach ($files as $file) {
                if (str_ends_with($file['name'], '.node.ts')) {
                    /** @var Response $downloadResp */
                    $downloadResp = Http::get($file['download_url']);
                    $ts .= $downloadResp->body();
                }

                if ($file['type'] === 'dir' && $file['name'] === 'properties') {
                    /** @var Response $propsResp */
                    $propsResp = Http::get($file['url']);
                    /** @var array|null $props */
                    $props = $propsResp->json();
                    foreach ($props as $p) {
                        if (str_ends_with($p['name'], '.ts')) {
                            /** @var Response $downloadResp2 */
                            $downloadResp2 = Http::get($p['download_url']);
                            $ts .= $downloadResp2->body();
                        }
                    }
                }
            }

            if (!$ts) continue;

            $schemas = $this->extractSchemas($ts, $node['name']);

            foreach ($schemas as $schema) {
                $this->store($schema);
            }
        }
    }

    private function extractSchemas(string $ts, string $node){
        $schemas = [];

        preg_match_all("/resource:\s*'([^']+)'/", $ts, $resources);
        preg_match_all("/name:\s*'([^']+)'[\s\S]*?displayName:\s*'([^']+)'/", $ts, $ops);

        preg_match_all(
            "/{[^}]*name:\s*'([^']+)'[^}]*type:\s*'([^']+)'[^}]*?(required:\s*true)?/m",
            $ts,
            $fields
        );

        $resources = array_unique($resources[1] ?: ['default']);

        foreach ($resources as $res) {
            foreach ($ops[1] as $i => $op) {
                $schemas[] = [
                    "node" => strtolower($node),
                    "resource" => $res,
                    "operation" => $op,
                    "display" => $ops[2][$i] ?? $op,
                    "fields" => $this->formatFields($fields)
                ];
            }
        }

        return $schemas;
    }

    private function formatFields($matches){
        $out = [];
        foreach ($matches[1] as $i => $name) {
            $out[] = [
                "name" => $name,
                "type" => $matches[2][$i],
                "required" => str_contains($matches[3][$i] ?? '', 'true')
            ];
        }
        return $out;
    }

    private function store(array $schema){
        $text = implode(' ', [
            $schema['node'],
            $schema['resource'],
            $schema['operation'],
            collect($schema['fields'])->pluck('name')->join(' ')
        ]);

        // generate vectors
        $denseVector  = IngestionService::embed($text);
        $sparseVector = IngestionService::buildSparseVector($text);

        $endpoint = rtrim("https://b66de96f-2a18-4cc1-9551-72590c427f65.europe-west3-0.gcp.cloud.qdrant.io", '/');
        $apiKey   = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhY2Nlc3MiOiJtIn0.cjPdIUH1DBKGMScZ5RgZ1Xv-zESkjGMS3H8acC_8D_c";

        $id = (string) Str::uuid();

        /** @var Response $response */
        $response = Http::withHeaders([
            'api-key' => $apiKey
        ])->put(
            $endpoint . '/collections/n8n_node_schemas/points?wait=true',
            [
                "points" => [[
                    "id" => $id,
                    "vector" => [
                        "dense-vector" => $denseVector,
                        "text-sparse"  => $sparseVector
                    ],
                    "payload" => $schema
                ]]
            ]
        );

        if (!$response->ok()) {
            $this->error("Qdrant insert failed: " . $response->body());
        }
    }

}
