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
        /** @var array|null $data */
        $results = self::getResults($user , $payload);

        if (!empty($results["error"])) {
            return [
                "success" => false,
                "errors" => $results["error"]
            ];
        }

        return [
            "success" => true,
            "output" => $results
        ];
    }

    private static function getResults($user , $payload){
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

        return $response->json();

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
