<?php

namespace App\Console\Commands;

use App\Console\Commands\Services\IngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;

class IngestN8nWorkflows extends Command{
    protected $signature = 'app:ingest-n8n-workflows';
    protected $description = 'Ingest production n8n workflows into Qdrant';

    private string $githubToken;
    private string $repoApi;

    public function __construct(){
        parent::__construct();
        $this->githubToken = env('GITHUB_TOKEN', '');
        $this->repoApi = env('GITHUB_REPO_WORKFLOWS_API', '');
    }

    public function handle(){
        $this->info("Fetching workflow folders...");

        // get workflow folders from GitHub repo
        $folders = $this->getWorkflowFolders();
       
        if (!is_array($folders)) {
            $this->error('Invalid folders response from GitHub: ' . json_encode($folders));
            return;
        }

        $this->processWorkflows($folders);

        $this->info("Done.");
    }

    private function processWorkflows(array $folders){
        foreach ($folders as $folder) {
            if ($folder['type'] !== 'file' || !str_ends_with($folder['name'], '.json')) {
                continue;
            }

            $this->info("Processing {$folder['name']}");

            $json =$this->downloadWorkflowJSON($folder['download_url']);
            $payload = $this->buildPayload($json);
            $embeddingText = $this->buildEmbeddingText($payload);

            try {
                $denseVector = IngestionService::embed($embeddingText);
                $sparseVector = IngestionService::buildSparseVector($embeddingText);

                $this->store($denseVector, $sparseVector, $payload);
            } catch (\Exception $ex) {
                $this->error("Failed to process {$folder['name']}: " . $ex->getMessage());
            }
        }
    }

    private function downloadWorkflowJSON(string $url): array{
        /** @var Response $response */
        $response = Http::withHeaders([
            'User-Agent' => 'Laravel-RAG',
            'Authorization' => 'token ' . $this->githubToken,
        ])->get($url);

        if (!$response->ok()) {
            throw new \RuntimeException("Failed to download workflow JSON: " . $response->body());
        }

        return $response->json();
    }

    private function getWorkflowFolders(){
        /** @var Response $response */
        $response = Http::withHeaders([
            'User-Agent' => 'Laravel-RAG',
            'Authorization' => 'token ' . $this->githubToken,
        ])->get($this->repoApi);

        if (!$response->ok()) {
            $this->error($response->body());
            return;
        }
        return $response->json();
    }

    private function buildPayload(array $json): array{
        $nodes = collect($json['nodes'] ?? [])
            ->pluck('type')
            ->map(fn($n) => Str::afterLast($n, '.'))
            ->unique()
            ->values()
            ->all();

        return [
            "workflow" => $json['name'] ?? '',
            "description" => $json['description'] ?? '',
            "notes" => $json['notes'] ?? '',
            "tags" => $json['tags'] ?? [],
            "category" => $json['meta']['category'] ?? null,
            "node_count" => count($json['nodes'] ?? []),
            "nodes_used" => $nodes,
            "raw" => $json
        ];
    }

    private function buildEmbeddingText(array $p): string{
        return implode("\n", [
            $p['workflow'],
            $p['description'],
            $p['notes'],
            "category: " . ($p['category'] ?? ''),
            "tags: " . implode(', ', $p['tags']),
            "nodes: " . implode(', ', $p['nodes_used'])
        ]);
    }

    private function store(array $dense, array $sparse, array $payload){
        $endpoint = env('QDRANT_CLUSTER_ENDPOINT', '');
        $apiKey = env('QDRANT_API_KEY', '');

        Http::withHeaders(['api-key' => $apiKey])->put(
            $endpoint . '/collections/n8n_workflows/points?wait=true',
            [
                "points" => [[
                    "id" => (string) Str::uuid(),
                    "vector" => [
                        "dense-vector" => $dense,
                        "text-sparse" => $sparse
                    ],
                    "payload" => $payload
                ]]
            ]
        );
    }

}
