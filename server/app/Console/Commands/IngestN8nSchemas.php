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

    public function handle(): int{
        $this->info('Starting n8n schema ingestion');
        Log::info('[n8n] Schema ingestion started');

        $count = $this->ingestSchemas();

        $this->newLine();
        $this->info("Finished. Successfully ingested {$count} schemas");
        $this->warn("Skipped {$this->skipped} schemas");

        Log::info("[n8n] Schema ingestion finished", [
            'success' => $count,
            'skipped' => $this->skipped,
        ]);

        return Command::SUCCESS;
    }

    private function ingestSchemas(string $jsonFilename = 'n8n_node_schemas.json', int $batchSize = 64): int{
        $items = $this->loadSchemasFromFile($jsonFilename);
        if (!$items) {
            return 0;
        }

        [$upsertUrl, $apiKey] = $this->resolveQdrantConfig();
        if (!$upsertUrl) {
            return 0;
        }

        return $this->processSchemas($items, $upsertUrl, $apiKey, $batchSize);
    }

    private function loadSchemasFromFile(string $jsonFilename): ?array{
        $filePath = base_path("../../../../microservice/ast-Schema-Extractor/" . $jsonFilename);

        $this->line("Loading schemas file: {$filePath}");
        Log::info("[n8n] Loading schemas file", ['path' => $filePath]);

        if (!file_exists($filePath)) {
            $this->error("Schemas file not found");
            Log::error("[n8n] Schemas file not found", ['path' => $filePath]);
            return null;
        }

        $items = json_decode(file_get_contents($filePath), true);

        if (!is_array($items)) {
            $this->error("Invalid JSON in schemas file");
            Log::error("[n8n] Invalid JSON in schemas file");
            return null;
        }

        $this->info("Loaded " . count($items) . " schemas");
        return $items;
    }

    private function resolveQdrantConfig(): array{
        $endpointBase = rtrim(env('QDRANT_CLUSTER_ENDPOINT', ''), '/');
        $apiKey = env('QDRANT_API_KEY', '');

        if (!$endpointBase) {
            $this->error('QDRANT_CLUSTER_ENDPOINT not set');
            Log::error('[n8n] Missing QDRANT_CLUSTER_ENDPOINT');
            return [null, null];
        }

        $collection = 'node_schemas';
        $upsertUrl = "{$endpointBase}/collections/{$collection}/points?wait=true";

        $this->info("Target collection: {$collection}");
        Log::info('[n8n] Target Qdrant collection', ['collection' => $collection]);

        return [$upsertUrl, $apiKey];
    }

    private function processSchemas(array $items, string $upsertUrl, string $apiKey, int $batchSize): int{
        $points = [];
        $successCount = 0;
        $total = count($items);

        foreach ($items as $index => $schema) {
            $point = $this->buildPointFromSchema($schema);
            if (!$point) {
                continue;
            }

            $points[] = $point;

            if (count($points) >= $batchSize || $index === array_key_last($items)) {
                $successCount += $this->upsertBatch($points, $upsertUrl, $apiKey, $successCount, $total);
                $points = [];
                usleep(100_000);
            }
        }

        return $successCount;
    }

    private function buildPointFromSchema(array $schema): ?array{
        $nodeId = $schema['node'] ?? null;
        if (!$nodeId) {
            $this->skipped++;
            Log::warning("[n8n] Missing node id", ['schema' => $schema]);
            return null;
        }

        $payload = $this->buildPayload($schema, $nodeId);
        $textForEmbedding = $this->buildEmbeddingText($payload);

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
            return null;
        }

        return [
            'id' => Str::uuid(),
            'vector' => [
                'dense-vector' => $denseVector,
                'text-sparse' => $sparseVector,
            ],
            'payload' => $payload,
        ];
    }

    private function buildPayload(array $schema, string $nodeId): array{
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

        return [
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
    }

    private function buildEmbeddingText(array $payload): string{
        return implode("\n", array_filter([
            $payload['displayName'],
            $payload['ai_summary'],
            $payload['description'],
            "resource: {$payload['resource']}",
            "operation: {$payload['operation']}",
            $payload['fields_names']
                ? 'fields: ' . implode(', ', $payload['fields_names'])
                : null,
        ]));
    }

    private function upsertBatch(array $points, string $upsertUrl, string $apiKey, int $currentSuccess, int $total): int{
        $this->line("Upserting batch of " . count($points));
        Log::info('[n8n] Upserting batch', ['count' => count($points)]);

        try {
            $resp = Http::withHeaders([
                'api-key' => $apiKey,
                'Accept' => 'application/json',
            ])->put($upsertUrl, ['points' => $points]);

            if ($resp->ok()) {
                $this->info("✅ Upserted " . ($currentSuccess + count($points)) . "/{$total}");
                return count($points);
            }

            $this->error("Qdrant said no");
            Log::error('[n8n] Qdrant upsert failed', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[n8n] Qdrant exception', ['error' => $e->getMessage()]);
        }

        return 0;
    }
}
