<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class IngestN8nNodes extends Command{
    protected $signature = 'app:ingest-n8n-nodes';
    protected $description = 'Ingest n8n node catalog from GitHub into Qdrant';

    public function handle(){
        $this->info("Fetching n8n node folders...");

        $response = Http::withHeaders([
            'User-Agent' => 'Laravel-RAG'
        ])->get('https://api.github.com/repos/n8n-io/n8n/contents/packages/nodes-base/nodes');

        if (!$response->ok()) {
            $this->error('GitHub API failed');
            return;
        }

        foreach ($response->json() as $folder) {
            if ($folder['type'] !== 'dir') continue;

            $this->info("Scanning {$folder['name']}");

            $files = Http::withHeaders([
                'User-Agent' => 'Laravel-RAG'
            ])->get($folder['url'])->json();

            $nodeJson = null;

            foreach ($files as $file) {
                if (str_ends_with($file['name'], '.node.json')) {
                    $nodeJson = $file['download_url'];
                    break;
                }
            }

            if (!$nodeJson) {
                $this->warn("No .node.json found for {$folder['name']}");
                continue;
            }

            $data = Http::get($nodeJson)->json();
            if (!$data) continue;

            $payload = $this->buildPayload($data);
            $this->storeInQdrant($payload);
        }

        $this->info("n8n node catalog ingestion completed.");
    }

    private function buildPayload(array $data): array{
        return [
            "id" => $data['node'],
            "node" => $data['node'],
            "key" => str_replace('n8n-nodes-base.', '', $data['node']),
            "categories" => $data['categories'] ?? [],
            "docs" => $data['resources']['primaryDocumentation'][0]['url'] ?? null,
            "credentials" => $data['resources']['credentialDocumentation'][0]['url'] ?? null,
            "codex" => $data['codexVersion'] ?? null,
        ];
    }

    private function storeInQdrant(array $node){
        $text = implode(' ', [
            $node['node'],
            implode(' ', $node['categories']),
            $node['docs'] ?? ''
        ]);

        $vector = $this->embed($text);

        Http::put(env('QDRANT_CLUSTER_ENDPOINT') . '/collections/n8n_catalog/points', [
            "points" => [
                [
                    "id" => $node['id'],
                    "vector" => $vector,
                    "payload" => $node
                ]
            ]
        ]);
    }

    private function embed(string $text): array{
        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post('https://api.openai.com/v1/embeddings', [
                "model" => "text-embedding-3-large",
                "input" => $text
            ]);

        return $response['data'][0]['embedding'];
    }
}
