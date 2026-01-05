<?php

namespace App\Console\Commands;

use App\Console\Commands\Services\IngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;

class IngestN8nNodes extends Command
{
    protected $signature = 'app:ingest-all-n8n-nodes';
    protected $description = 'Recursively ingest ALL n8n .node.json files into Qdrant';

    private int $ingested = 0;
    private int $skipped = 0;

    public function handle()
    {
        $root = 'https://api.github.com/repos/n8n-io/n8n/contents/packages/nodes-base/nodes';

        $this->info('Starting recursive ingestion of n8n nodes...');
        $this->crawl($root);

        $this->newLine();
        $this->info("Ingestion complete.");
        $this->info("Ingested: {$this->ingested}");
        $this->info("Skipped: {$this->skipped}");
    }

    private function crawl(string $url): void{
        /** @var Response $response */
        $response = Http::withHeaders([
            'User-Agent'    => 'Laravel-RAG',
            'Authorization' => 'token ' . env('GITHUB_TOKEN'),
        ])->timeout(30)->get($url);

        if (!$response->ok()) {
            $this->warn("Failed to fetch: {$url}");
            return;
        }

        $items = $response->json();
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item['type'] === 'dir') {
                // recurse
                $this->crawl($item['url']);
                continue;
            }

            if (
                $item['type'] === 'file' &&
                str_ends_with($item['name'], '.node.json')
            ) {
                $this->ingestNodeFile($item['download_url']);
            }
        }
    }


    private function ingestNodeFile(string $url): void
    {
        $this->line("→ {$url}");

        $data = Http::get($url)->json();
        if (!$data || empty($data['node'])) {
            $this->skipped++;
            return;
        }

        $payload = $this->buildPayload($data);
        $this->storeInQdrant($payload);

        $this->ingested++;
    }


    private function buildPayload(array $data): array
    {
        $node = $data['node'];
        $key = strtolower(str_replace('n8n-nodes-base.', '', $node));

        return [
            'node_id'        => $node,
            'node'           => $node,
            'key'            => $key,
            'key_normalized' => preg_replace('/[^a-z0-9]/', '', $key),
            'categories'     => array_map('strtolower', $data['categories'] ?? []),
            'docs'           => $data['resources']['primaryDocumentation'][0]['url'] ?? null,
            'credentials'    => $data['resources']['credentialDocumentation'][0]['url'] ?? null,
            'codex'          => $data['codexVersion'] ?? null,
        ];
    }


    private function storeInQdrant(array $node): void
    {
        $text = implode(' ', [
            $node['node'],
            $node['key'],
            implode(' ', $node['categories']),
            $node['docs'] ?? '',
        ]);

        $denseVector = IngestionService::embed($text);
        if (count($denseVector) !== 3072) {
            $this->warn('Embedding size mismatch, skipping node.');
            $this->skipped++;
            return;
        }

        $sparseVector = IngestionService::buildSparseVector($text);

        $endpoint = rtrim(env('QDRANT_CLUSTER_ENDPOINT', ''), '/');

        Http::withHeaders([
            'api-key' => env('QDRANT_API_KEY'),
        ])->put(
            $endpoint . '/collections/n8n_catalog/points?wait=true',
            [
                'points' => [
                    [
                        'id'     => (string) Str::uuid(),
                        'vector' => [
                            'dense-vector' => $denseVector,
                            'text-sparse'  => $sparseVector,
                        ],
                        'payload' => $node,
                    ],
                ],
            ]
        );
    }
}
