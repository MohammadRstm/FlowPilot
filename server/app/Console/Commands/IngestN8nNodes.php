<?php

namespace App\Console\Commands;

use App\Console\Commands\Services\IngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;
use Illuminate\Validation\Rules\In;

class IngestN8nNodes extends Command{
    protected $signature = 'app:ingest-n8n-nodes';
    protected $description = 'Ingest n8n node catalog from GitHub into Qdrant';

    public function handle(){
        $this->info("Fetching n8n node folders...");
        $folders = $this->getFolders();
        
        foreach ($folders as $folder) {
            if ($folder['type'] !== 'dir') continue;

            $this->info("Scanning {$folder['name']}");

            $files = $this->getFiles($folder['url']);

            if (!is_array($files)) {
                $this->warn("Skipping {$folder['name']}, files not found or invalid response.");
                continue;
            }

            $nodeJson = $this->getNodeJson($files);

            if(!$nodeJson){
                $this->warn("No .node.json found for {$folder['name']}");
                continue;
            }

            $data = Http::get($nodeJson)->json();
            if(!$data) continue;

            $payload = $this->buildPayload($data);
            $this->storeInQdrant($payload);
        }

        $this->info("n8n node catalog ingestion completed.");
    }

    private function getNodeJson(array $files): ?string{
        foreach ($files as $file) {
            if ($file['type'] === 'file' && str_ends_with($file['name'], '.node.json')) {
                return $file['download_url'];
            }
        }
        return null;
    }



    private function getFiles(string $url){
        /** @var Response $filesResponse */
        $filesResponse = Http::withHeaders([
            'User-Agent' => 'Laravel-RAG',
            'Authorization' => 'token ' . env('GITHUB_TOKEN')
        ])->get($url);

        return $filesResponse->json();
    }
        


    private function getFolders(){
        /** @var Response $response */
        $response = Http::withHeaders([
            'User-Agent' => 'Laravel-RAG',
            'Authorization' => 'token ' . env('GITHUB_TOKEN')
        ])->timeout(30)->get(env('GITHUB_REPO_NODES_API', ''));

        if(!$response->ok()){
            $this->error('GitHub API failed. Status: ' . $response->status());
            $this->error('Response body: ' . $response->body());
            throw new \RuntimeException("Failed to fetch n8n node folders from GitHub");
        }

        $this->info('X-RateLimit-Limit: ' . $response->header('X-RateLimit-Limit'));
        $this->info('X-RateLimit-Remaining: ' . $response->header('X-RateLimit-Remaining'));

        return $response->json();
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

        // Dense embedding
        $denseVector = IngestionService::embed($text);
        if (count($denseVector) !== 3072) {
            $this->error("Embedding size mismatch: " . count($denseVector));
            return;
        }

        // Sparse vector
        $sparseVector = IngestionService::buildSparseVector($text);

        $endpoint = rtrim(env('QDRANT_CLUSTER_ENDPOINT', 'https://b66de96f-2a18-4cc1-9551-72590c427f65.europe-west3-0.gcp.cloud.qdrant.io'), '/');
        $apiKey = env('QDRANT_API_KEY');

        $id = (string) Str::uuid();

        /** @var Response $response */
        $response = Http::withHeaders([
            "api-key" => $apiKey
        ])->put(
            $endpoint . '/collections/n8n_catalog/points?wait=true',
            [
                "points" => [
                    [
                        "id" => $id,
                        "vector" => [
                            "dense-vector" => $denseVector,
                            "text-sparse"  => $sparseVector
                        ],
                        "payload" => $node
                    ]
                ]
            ]
        );

        $this->info($response->body());

        if (!$response->ok()) {
            $this->error("Failed storing node {$node['id']}: " . $response->body());
        }
    }
}
