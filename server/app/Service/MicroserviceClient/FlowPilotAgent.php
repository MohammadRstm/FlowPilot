<?php

namespace App\Service\MicroserviceClient;

use Exception;
use Illuminate\Support\Facades\Http;

class FlowPilotAgent{

    public static function buildWorkflow(string $question){
        $endpoint = env('MICROSERVICE_FLOWPILOT_AGENT_WORKFLOW_BUILD_URL');
        /** @var Response */
        $response = Http::timeout(120)
            ->acceptJson()
            ->post($endpoint . '/build-workflow', [
                'question' => $question
            ]);

        if (!$response->successful()) {
            throw new Exception(
                "FlowPilot service error: " . $response->body()
            );
        }

        return $response->json();
    }


   
}
