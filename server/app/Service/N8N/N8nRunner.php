<?php

namespace App\Service\N8N;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class N8nRunner{
    
    public static function run(array $workflow,array $user): array {

        $mockData = MockDataGenerator::fromWorkflow($workflow);

        if ($mockData === null) {
            return [
                'success' => true,
                'output' => null,
                'warning' => 'Workflow has a complex trigger and cannot be run with mock data. It is runnable with real trigger events.'
            ];
        }

        $payload = self::generatePayload($workflow, $mockData);       
        /** @var array $results */
        $results = self::getResults($user , $payload);

        if (isset($results['success']) && $results['success'] === false) {
            return [
                'success' => false,
                'errors' => $results['errors'] ?? $results['error'] ?? $results
            ];
        }

        if (isset($results['error']) || isset($results['errors'])) {
            return [
                'success' => false,
                'errors' => $results['errors'] ?? $results['error'] ?? $results
            ];
        }

        return [
            'success' => true,
            'output' => $results
        ];
    }

    private static function getResults($user , $payload): array{
         /** @var Response $response */
        $response = Http::withToken($user["n8n_api_key"])
            ->timeout(60)
            ->post($user["n8n_url"] . "/rest/workflows/run", $payload);

        if (!$response->ok()) {
            return [
                "success" => false,
                "errors" => $response->body()
            ];
        }

        /** @var array|null $json */
        $json = $response->json();

        return $json ?? [];

    }

    private static function generatePayload(array $workflow, array $mockData = []): array{
        return [
            "workflowData" => $workflow,    
            "runData" => [
                "trigger" => [
                    [
                        "json" => $mockData ?: ["test" => true]
                    ]
                ]
            ],
            "startNodes" => null
        ];
    }
}
