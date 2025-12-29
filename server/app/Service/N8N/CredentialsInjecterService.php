<?php

namespace App\Service\N8N;

use Illuminate\Support\Facades\Http;

class CredentialsInjecterService{
    /**
     * Injects credentials for a given workflow and user.
     *
     * @param array $workflow AI-generated workflow JSON
     * @param \App\Models\User $user
     * @return array Workflow with credentials injected safely
     */
    public static function inject(array $workflow, $user): array
    {
        // Step 0: Safety check
        if (empty($user->n8n_url) || empty($user->n8n_api_key)) {
            // User hasn't connected n8n → return workflow untouched
            return $workflow;
        }

        // Step 1: Fetch user's credentials from n8n
        $response = Http::withToken($user->n8n_api_key)
            ->timeout(10)
            ->get(rtrim($user->n8n_url, '/') . '/rest/credentials');

        if (!$response->ok()) {
            // Could not fetch credentials, skip injection
            return $workflow;
        }

        $userCredentials = collect($response->json())->keyBy('type');

        // Step 2: Scan workflow nodes for required credentials
        foreach ($workflow['nodes'] as &$node) {
            if (empty($node['type'])) continue;

            $credType = self::getCredentialTypeFromNode($node['type']);

            if (!$credType) continue; // Node doesn't need credentials

            // Inject only if user has it
            if (isset($userCredentials[$credType])) {
                $node['credentials'][$credType] = [
                    'id' => $userCredentials[$credType]['id'],
                    'name' => $userCredentials[$credType]['name'],
                ];
            }
            // If user doesn't have credential → skip injection (do not break workflow)
        }

        return $workflow;
    }

    /**
     * Returns the credential type needed for a given node type.
     * Extend this mapping as needed.
     *
     * @param string $nodeType
     * @return string|null
     */
    private static function getCredentialTypeFromNode(string $nodeType): ?string
    {
        $map = [
            'n8n-nodes-base.slack' => 'slackApi',
            'n8n-nodes-base.googleSheets' => 'googleSheetsOAuth2',
            'n8n-nodes-base.googleDrive' => 'googleDriveOAuth2',
            'n8n-nodes-base.acuitySchedulingTrigger' => 'acuitySchedulingApi',
            'n8n-nodes-base.notion' => 'notionApi',
            // Add more mappings as your node coverage grows
        ];

        return $map[$nodeType] ?? null;
    }
}
