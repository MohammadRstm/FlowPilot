<?php

namespace App\Console\Commands;

use App\Console\Commands\Services\IngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IngestN8nSchemas extends Command
{
    protected $signature = 'n8n:harvest-schemas';
    protected $description = 'Harvest n8n node schemas and store them in Qdrant';

    private int $skipped = 0;

    public function handle(): int
    {
        $this->info('🚀 Starting n8n schema ingestion');
        Log::info('[n8n] Schema ingestion started');

        $count = $this->storeSchemasFileInQdrant();

        $this->newLine();
        $this->info("✅ Finished. Successfully ingested {$count} schemas");
        $this->warn("⚠️ Skipped {$this->skipped} schemas");

        Log::info("[n8n] Schema ingestion finished", [
            'success' => $count,
            'skipped' => $this->skipped,
        ]);

        return Command::SUCCESS;
    }

    private function storeSchemasFileInQdrant(
        string $jsonFilename = 'n8n_node_schemas.json',
        int $batchSize = 64
    ): int {
        $filePath = base_path("app/Console/Commands/" . $jsonFilename);

        $this->line("📄 Loading schemas file: {$filePath}");
        Log::info("[n8n] Loading schemas file", ['path' => $filePath]);

        if (!file_exists($filePath)) {
            $this->error("❌ Schemas file not found");
            Log::error("[n8n] Schemas file not found", ['path' => $filePath]);
            return 0;
        }

        $items = json_decode(file_get_contents($filePath), true);

        if (!is_array($items)) {
            $this->error("❌ Invalid JSON in schemas file");
            Log::error("[n8n] Invalid JSON in schemas file");
            return 0;
        }

        $total = count($items);
        $this->info("📦 Loaded {$total} schemas");

        $endpointBase = rtrim(env('QDRANT_CLUSTER_ENDPOINT', ''), '/');
        $apiKey = env('QDRANT_API_KEY', '');

        if (!$endpointBase) {
            $this->error('❌ QDRANT_CLUSTER_ENDPOINT not set');
            Log::error('[n8n] Missing QDRANT_CLUSTER_ENDPOINT');
            return 0;
        }

        $collection = 'test';
        $upsertUrl = "{$endpointBase}/collections/{$collection}/points?wait=true";

        $this->info("📤 Target collection: {$collection}");
        Log::info('[n8n] Target Qdrant collection', ['collection' => $collection]);

        $points = [];
        $successCount = 0;
        $index = 0;

        foreach ($items as $schema) {
            $index++;

            $nodeId = $schema['node'] ?? null;
            if (!$nodeId) {
                $this->skipped++;
                Log::warning("[n8n] Missing node id", ['schema' => $schema]);
                continue;
            }

            $display = $schema['displayName'] ?? $nodeId;
            $description = $schema['description'] ?? '';
            $aiSummary = $schema['ai_summary'] ?? '';
            $resource = $schema['resource'] ?? 'default';
            $operation = $schema['operation'] ?? 'default';
            $fields = $schema['fields'] ?? [];
            $credentials = $schema['credentials'] ?? [];

            $fieldNames = array_values(array_filter(
                array_map(fn ($f) => $f['name'] ?? null, $fields)
            ));

            $isTrigger =
                stripos($nodeId, 'trigger') !== false ||
                stripos($display, 'trigger') !== false;

            $payload = [
                'id_source' => "{$nodeId}::{$resource}::{$operation}",
                'node' => $nodeId,
                'node_normalized' => strtolower(preg_replace('/[^a-z0-9]/i', '', $nodeId)),
                'displayName' => $display,
                'resource' => $resource,
                'operation' => $operation,
                'is_trigger' => $isTrigger,
                'credentials' => $credentials,
                'description' => $description,
                'ai_summary' => $aiSummary,
                'fields_names' => $fieldNames,
                'indexed_at' => now()->toIso8601String(),
            ];

            $textForEmbedding = implode("\n", array_filter([
                $display,
                $aiSummary,
                $description,
                "resource: {$resource}",
                "operation: {$operation}",
                $fieldNames ? 'fields: ' . implode(', ', $fieldNames) : null,
            ]));

            try {
                $denseVector = IngestionService::embed($textForEmbedding);
                $sparseVector = IngestionService::buildSparseVector($textForEmbedding);
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->error("Embedding failed, skipping node");
                Log::error("[n8n] Embedding failed", [
                    'node' => $nodeId,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            $points[] = [
                'id' => Str::uuid(),
                'vector' => [
                    'dense-vector' => $denseVector,
                    'text-sparse' => $sparseVector,
                ],
                'payload' => $payload,
            ];

            if (count($points) >= $batchSize || $index === $total) {
                $this->line("⬆️  Upserting batch of " . count($points));
                Log::info('[n8n] Upserting batch', ['count' => count($points)]);

                try {
                    /** @var Response */
                    $resp = Http::withHeaders([
                        'api-key' => $apiKey,
                        'Accept' => 'application/json',
                    ])->put($upsertUrl, ['points' => $points]);

                    if ($resp->ok()) {
                        $successCount += count($points);
                        $this->info("✅ Upserted {$successCount}/{$total}");
                    } else {
                        $this->error("Qdrant said no");
                        Log::error('[n8n] Qdrant upsert failed', [
                            'status' => $resp->status(),
                            'body' => $resp->body(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('[n8n] Qdrant exception', ['error' => $e->getMessage()]);
                }

                $points = [];
                usleep(100_000);
            }
        }

        return $successCount;
    }
}
