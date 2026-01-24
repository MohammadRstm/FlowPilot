<?php

namespace App\Service\Copilot;

use App\Console\Commands\Services\IngestionService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SaveWorkflow{

    public static function save($requestForm){
        $json = json_encode($requestForm->input('workflow'));
        if(!$json){
            throw new Exception("Workflow given not correct json");
        }

        $question = $requestForm["question"];
        $payload = self::buildPayload($json , $question);
        $embeddingText = self::buildEmbeddingText($payload);

        try{
            $denseVector = IngestionService::embed($embeddingText);
            $sparseVector = IngestionService::buildSparseVector($embeddingText);

            $endpoint = rtrim(env('QDRANT_CLUSTER_ENDPOINT', ''), '/');

            Http::withHeaders([
                'api-key' => env('QDRANT_API_KEY'),
            ])->put(
                $endpoint . '/collections/n8n_workflows/points?wait=true',
                [
                    'points' => [
                        [
                            'id'     => (string) Str::uuid(),
                            'vector' => [
                                'dense-vector' => $denseVector,
                                'text-sparse'  => $sparseVector,
                            ],
                            'payload' => $payload,
                        ],
                    ],
                ]
            );

            return $payload;
        } catch (\Exception $e) {
            Log::error("Failed to store workflow", ['error' => $e->getMessage()]);
            throw new Exception("failed to store workflow in qdrant " . $e->getMessage());
        }
    }

    private static function buildPayload(string $json , string $question): array {
        $metaData = LLMService::generateWorkflowQdrantPayload($json , $question); // description, tags, notes, category

        $decoded_workflow = json_decode($json , true);
        if (!is_array($decoded_workflow)) {
            throw new \RuntimeException("Invalid workflow JSON passed to buildPayload");
        }

        $nodes = array_map(fn($n) => $n['name'] ?? $n['type'] ?? '', $decoded_workflow['nodes'] ?? []);

        return [
            "workflow" => $decoded_workflow['name'] ?? '',
            "description" => $metaData['description'] ?? '',
            "notes" => $metaData['notes'] ?? '',
            "tags" => is_array($metaData['tags'] ?? null) ? $metaData['tags'] : [],
            "category" => $metaData['category'] ?? null,
            "node_count" => count($decoded_workflow['nodes'] ?? []),
            "nodes_used" => $nodes,
            "raw" => $decoded_workflow
        ];
    }

    private static function buildEmbeddingText(array $p): string{
        return implode("\n", [
            $p['workflow'],
            $p['description'],
            $p['notes'],
            "category: " . ($p['category'] ?? ''),
            "tags: " . implode(', ', $p['tags']),
            "nodes: " . implode(', ', $p['nodes_used'])
        ]);
    }
}
