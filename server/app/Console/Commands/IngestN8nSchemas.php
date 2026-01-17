<?php

namespace App\Console\Commands;

use App\Console\Commands\Services\IngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IngestN8nSchemas extends Command
{
    protected $signature = 'app:ingest-n8n-schemas';
    protected $description = 'Ingest n8n node schemas from output.json into Qdrant with batching, retries, and resumable progress';

    protected int $batchSize = 50;   // Adjust batch size if needed
    protected int $maxRetries = 5;   // Max retry attempts
    protected int $retryDelay = 2;   // Initial delay in seconds (exponential backoff)
    protected string $progressFile = __DIR__ . '/ingestion_progress.json';

    public function handle()
    {
        $jsonPath = __DIR__ . "/../../../Schema-Extractor/output.json";

        if (!file_exists($jsonPath)) {
            $this->error("File not found: $jsonPath");
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        if (!is_array($data)) {
            $this->error("Invalid JSON format in $jsonPath");
            return;
        }

        $this->info("Ingesting " . count($data) . " node schemas...");

        // Check progress file to resume
        $lastProcessedBatch = 0;
        if (file_exists($this->progressFile)) {
            $progress = json_decode(file_get_contents($this->progressFile), true);
            if (isset($progress['last_batch'])) {
                $lastProcessedBatch = (int)$progress['last_batch'];
                $this->info("Resuming from batch " . ($lastProcessedBatch + 1));
            }
        }

        $batches = array_chunk($data, $this->batchSize);
        $batchCount = count($batches);

        for ($i = $lastProcessedBatch; $i < $batchCount; $i++) {
            $this->info("Processing batch " . ($i + 1) . " / $batchCount ...");
            $this->storeBatchWithRetry($batches[$i]);

            // Save progress after successful batch
            file_put_contents($this->progressFile, json_encode(['last_batch' => $i]));
        }

        $this->info("Done!");
        if (file_exists($this->progressFile)) {
            unlink($this->progressFile);
        }
    }

    private function storeBatchWithRetry(array $batch)
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $this->storeBatch($batch);
                return; // success
            } catch (\Exception $e) {
                $attempt++;
                $delay = $this->retryDelay * (2 ** ($attempt - 1));
                $this->error("Batch insert failed (attempt $attempt): " . $e->getMessage());
                $this->info("Retrying in $delay seconds...");
                sleep($delay);
            }
        }

        throw new \RuntimeException("Batch insert failed after {$this->maxRetries} attempts.");
    }

    private function storeBatch(array $batch)
    {
        $endpoint = rtrim(env('QDRANT_CLUSTER_ENDPOINT', ''), '/');
        $apiKey   = env('QDRANT_API_KEY', '');

        $points = [];

        foreach ($batch as $schema) {
            $schema = $this->normalizeSchema($schema);

            // Ensure fields array exists
            $fieldsText = collect($schema['fields'] ?? [])->pluck('name')->join(' ');

            $text = implode(' ', [
                $schema['node'],
                $schema['resource'],
                $schema['operation'],
                $fieldsText
            ]);

            $denseVector  = IngestionService::embed($text);
            $sparseVector = IngestionService::buildSparseVector($text);

            $points[] = [
                "id" => (string) Str::uuid(),
                "vector" => [
                    "dense-vector" => $denseVector,
                    "text-sparse"  => $sparseVector
                ],
                "payload" => $schema
            ];
        }
        /** @var Response */
        $response = Http::withHeaders(['api-key' => $apiKey])
            ->timeout(1000)
            ->put($endpoint . '/collections/n8n_node_schemas/points?wait=true', [
                "points" => $points
            ]);

        if (!$response->ok()) {
            throw new \RuntimeException("Qdrant insert failed: " . $response->body());
        }

        foreach ($batch as $schema) {
            $this->info("Stored schema for node: {$schema['node']} - resource: {$schema['resource']} - operation: {$schema['operation']}");
        }
    }

    /**
     * Normalize a single schema to guarantee it matches the full structure.
     */
    private function normalizeSchema(array $schema): array
    {
        // Ensure fields array exists
        if (empty($schema['fields']) && !empty($schema['fieldName'])) {
            $schema['fields'] = [
                [
                    'name' => $schema['fieldName'],
                    'display' => $schema['display'] ?? $schema['fieldName'],
                    'type' => $schema['type'] ?? 'string',
                    'required' => $schema['required'] ?? false,
                    'description' => $schema['description'] ?? ''
                ]
            ];
        }

        return [
            'node' => $schema['node'] ?? 'unknown',
            'node_normalized' => $schema['node_normalized'] ?? strtolower(preg_replace('/[^a-z0-9]/i', '', $schema['node'] ?? 'unknown')),
            'resource' => $schema['resource'] ?? 'default',
            'operation' => $schema['operation'] ?? 'default',
            'display' => $schema['display'] ?? ($schema['fieldName'] ?? 'Unknown'),
            'fieldName' => $schema['fieldName'] ?? 'unknown',
            'type' => $schema['type'] ?? 'string',
            'required' => $schema['required'] ?? false,
            'description' => $schema['description'] ?? '',
            'fields' => $schema['fields'] ?? [],
            'inputs' => $schema['inputs'] ?? [
                [
                    'name' => 'Previous Node Output',
                    'type' => 'JSON',
                    'required' => false,
                    'description' => 'Optional input from a previous node',
                ]
            ],
            'outputs' => $schema['outputs'] ?? [
                [
                    'name' => 'Output',
                    'type' => 'JSON',
                    'description' => 'Generic JSON output; expand with specific fields if available',
                    'fields' => []
                ]
            ]
        ];
    }
}
