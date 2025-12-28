<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class IngestN8nSchemas extends Command{

    protected $signature = 'app:ingest-n8n-schemas';
    protected $description = 'Extract n8n node schemas from TypeScript and store in Qdrant';

    public function handle(){
        /** @var Response $response */
        $response = Http::withHeaders([
            'User-Agent' => 'Laravel-RAG',
            'Authorization' => 'token ' . env('GITHUB_TOKEN')
        ])->get(
            'https://api.github.com/repos/n8n-io/n8n/contents/packages/nodes-base/nodes'
        );

        if(!$response->ok()){
            $this->error('GitHub API failed. Status: ' . $response->status());
            $this->error('Response body: ' . $response->body());
            return;
        }

        $nodes = $response->json();
        
        foreach ($nodes as $node) {
            if ($node['type'] !== 'dir') continue;

            $this->info("Parsing {$node['name']}");

            $files = Http::get($node['url'])->json();

            $ts = '';

            foreach ($files as $file) {
                if (str_ends_with($file['name'], '.node.ts')) {
                    $ts .= Http::get($file['download_url'])->body();
                }

                if ($file['type'] === 'dir' && $file['name'] === 'properties') {
                    $props = Http::get($file['url'])->json();
                    foreach ($props as $p) {
                        if (str_ends_with($p['name'], '.ts')) {
                            $ts .= Http::get($p['download_url'])->body();
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

        $vector = $this->embed($text);

        Http::put(env('QDRANT_CLUSTER_ENDPOINT') . '/collections/n8n_node_schemas/points', [
            "points" => [[
                "id" => uniqid(),
                "vector" => $vector,
                "payload" => $schema
            ]]
        ]);
    }

    private function embed(string $text){
        $r = Http::withToken(env('OPENAI_API_KEY'))
            ->post('https://api.openai.com/v1/embeddings', [
                "model" => "text-embedding-3-large",
                "input" => $text
            ]);

        return $r['data'][0]['embedding'];
    }
}
