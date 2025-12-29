<?php

namespace App\Service\N8N;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class CredentialsInjecterService{
  
    public static function inject(array $workflow, $user): array{
       
        $userCredentials = self::getUserCredentials($workflow , $user);

        // get required credentials for each node & inject
        foreach ($workflow['nodes'] as &$node) {
            if (empty($node['type'])) continue;

            $credType = self::getCredentialTypeFromNode($node['type']);

            if (!$credType) continue; // Node doesn't need credentials

            if (isset($userCredentials[$credType])) {
                $node['credentials'][$credType] = [
                    'id' => $userCredentials[$credType]['id'],
                    'name' => $userCredentials[$credType]['name'],
                ];
            }
        }

        return $workflow;
    }

    private static function getUserCredentials($workflow , $user){
         /** @var Response $response */
        $response = Http::withToken($user["n8n_api_key"])
            ->timeout(10)
            ->get(rtrim($user["n8n_url"], '/') . '/rest/credentials');

        if(!$response->ok()){
            return $workflow;
        }

        /** @var array|null $creds */
        $creds = $response->json();
        if (!is_array($creds)) {
            return $workflow;
        }
        return collect($creds)->keyBy('type');
    }

    private static function getCredentialTypeFromNode(string $nodeType): ?string{
        $map = [
            'n8n-nodes-base.slack' => 'slackApi',
            'n8n-nodes-base.googleSheets' => 'googleSheetsOAuth2',
            'n8n-nodes-base.googleDrive' => 'googleDriveOAuth2',
            'n8n-nodes-base.acuitySchedulingTrigger' => 'acuitySchedulingApi',
            'n8n-nodes-base.notion' => 'notionApi',
        ];

        return $map[$nodeType] ?? null;
    }
}
