<?php

namespace App\Service\N8N;

use Illuminate\Support\Facades\Http;

class N8nRunner{
    
    public static function run(array $workflow, array $mockData = []) {

        $payload = [
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

        $response = Http::withToken(env("N8N_API_KEY"))
            ->timeout(60)
            ->post(env("N8N_URL") . "/rest/workflows/run", $payload);

        if (!$response->ok()) {
            return [
                "success" => false,
                "errors" => $response->body()
            ];
        }

        $data = $response->json();

        if (!empty($data["error"])) {
            return [
                "success" => false,
                "errors" => $data["error"]
            ];
        }

        return [
            "success" => true,
            "output" => $data
        ];
    }
}
