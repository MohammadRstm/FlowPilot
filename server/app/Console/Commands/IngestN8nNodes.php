<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;

class IngestN8nNodes extends Command{
    protected $signature = 'app:ingest-n8n-nodes';
    protected $description = 'Ingest n8n node catalog from GitHub into Qdrant';

    public function handle(){
        $this->info("Fetching n8n node folders...");

        /** @var Response $response */
        $response = Http::withHeaders([
            'User-Agent' => 'Laravel-RAG',
            'Authorization' => 'token ' . env('GITHUB_TOKEN')
        ])->timeout(30)->get('https://api.github.com/repos/n8n-io/n8n/contents/packages/nodes-base/nodes');

        if(!$response->ok()){
            $this->error('GitHub API failed. Status: ' . $response->status());
            $this->error('Response body: ' . $response->body());
            return;
        }

        $this->info('X-RateLimit-Limit: ' . $response->header('X-RateLimit-Limit'));
        $this->info('X-RateLimit-Remaining: ' . $response->header('X-RateLimit-Remaining'));
        
        foreach ($response->json() as $folder) {
            if ($folder['type'] !== 'dir') continue;

            $this->info("Scanning {$folder['name']}");

            /** @var Response $filesResponse */
            $filesResponse = Http::withHeaders([
                'User-Agent' => 'Laravel-RAG',
                'Authorization' => 'token ' . env('GITHUB_TOKEN')
            ])->get($folder['url']);

            /** @var array|null $files */
            $files = $filesResponse->json();

            if (!is_array($files)) {
                $this->warn("Skipping {$folder['name']}, files not found or invalid response.");
                continue;
            }

            $nodeJson = null;

            foreach($files as $file){
                if(!is_array($file)) continue;
                if(isset($file['name']) && str_ends_with($file['name'], '.node.json')) {
                    $nodeJson = $file['download_url'];
                    break;
                }
            }

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
        $denseVector = $this->embed($text);
        if (count($denseVector) !== 3072) {
            $this->error("Embedding size mismatch: " . count($denseVector));
            return;
        }

        // Sparse vector
        $sparseVector = $this->buildSparseVector($text);

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

    private function buildSparseVector(string $text): array{
        // normalize
        $text = strtolower($text);

        // split camelCase
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);

        // tokenize
        $tokens = preg_split('/[^a-z0-9]+/i', $text);

        $freqs = [];

        foreach ($tokens as $token) {
            if (strlen($token) < 2) continue;

            $freqs[$token] = ($freqs[$token] ?? 0) + 1;
        }

        $indices = [];
        $values  = [];

        foreach ($freqs as $token => $count) {
            // stable token id
            $tokenId = crc32($token);

            $indices[] = $tokenId;
            $values[]  = (float) $count; // RAW term frequency
        }

        return [
            'indices' => $indices,
            'values'  => $values,
        ];
    }



    private function embed(string $text): array{
        /** @var Response $response */
        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/embeddings', [
                "model" => "text-embedding-3-large",
                "input" => $text
            ]);

        if (!$response->ok()) {
            $this->error("OpenAI embedding error: " . $response->body());
            return [];
        }

        $json = $response->json();
        if (!isset($json['data'][0]['embedding'])) {
            $this->error("Invalid OpenAI response: " . json_encode($json));
            return [];
        }

        return $json['data'][0]['embedding'];
    }

}
