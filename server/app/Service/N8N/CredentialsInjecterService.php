<?php

namespace App\Service\N8N;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class CredentialsInjecterService{
  
    public static function inject(array $workflow, $user): array{
       
        $userCredentials = self::getUserCredentials($workflow , $user);
        Log::info('Fetched user credentials for injection', ['credentials' => json_encode($userCredentials)]);

        // get required credentials for each node & inject
        foreach ($workflow['nodes'] as &$node) {
            if (empty($node['type'])) continue;

            $credType = self::getCredentialTypeFromNode($node['type']);

            if (!$credType) continue; // Node doesn't need credentials

            if (isset($userCredentials[$credType])) {
                Log::info('Injecting credential', ['node_type' => $node['type'], 'credential_type' => $credType]);
                $node['credentials'][$credType] = [
                    'id' => $userCredentials[$credType]['id'],
                    'name' => $userCredentials[$credType]['name'],
                ];
            }
        }

        Log::info('Injected user credentials into workflow');

        return $workflow;
    }

    private static function getUserCredentials($workflow , $user){
        try {
            /** @var Response response */
            $response = Http::withHeaders([
                    'X-N8N-API-KEY' => $user["n8n_api_key"],
                    'Content-type' => 'application/json'
                ])
                ->timeout(30)
                ->get(rtrim($user["n8n_url"], '/') . '/api/v1/credentials');
        } catch (\Throwable $e) {
            Log::error('HTTP request failed fetching user credentials from n8n', [
                'error' => $e->getMessage(),
                'user' => $user['id'] ?? null,
            ]);

            return $workflow;
        }

        $status = $response->status();
        $ok = $response->ok();
        $body = $response->body();

        Log::info('Response of user credentials from n8n instance', [
            'status' => $status,
            'ok' => $ok,
            'body_preview' => strlen($body) > 200 ? substr($body, 0, 200) . '...[truncated]' : $body,
            'user' => $user['id'] ?? null,
        ]);

        if (!$ok) {
            Log::error('Failed fetching user credentials from n8n', [
                'status' => $status,
                'body' => $body,
                'user' => $user['id'] ?? null,
            ]);

            return $workflow;
        }

        /** @var array|null $creds */
        try {
            $creds = $response->json();
        } catch (\Throwable $e) {
            Log::error('Failed decoding credentials JSON from n8n response', [
                'error' => $e->getMessage(),
                'body' => $body,
                'user' => $user['id'] ?? null,
            ]);

            return $workflow;
        }

        if (!is_array($creds)) {
            Log::warning('Credentials response is not an array', [
                'body' => $body,
                'user' => $user['id'] ?? null,
            ]);

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
            'n8n-nodes-base.openai' => 'openAIApi',
            'n8n-nodes-base.twitter' => 'twitterOAuth1Api',
            'n8n-nodes-base.github' => 'githubOAuth2Api',
            'n8n-nodes-base.dropbox' => 'dropboxOAuth2Api',
            'n8n-nodes-base.microsoftExcel' => 'microsoftExcelOAuth2Api',
            'n8n-nodes-base.microsoftOneDrive' => 'microsoftOneDriveOAuth2Api',
            'n8n-nodes-base.microsoftOutlook' => 'microsoftOutlookOAuth2Api',
            'n8n-nodes-base.googleContacts' => 'googleContactsOAuth2',
            'n8n-nodes-base.salesforce' => 'salesforceOAuth2Api',
            'n8n-nodes-base.asana' => 'asanaOAuth2Api',
            'n8n-nodes-base.trello' => 'trelloApi',
            'n8n-nodes-base.mongodb' => 'mongoDbConnection',
            'n8n-nodes-base.postgres' => 'postgresConnection',
            'n8n-nodes-base.mysql' => 'mySqlConnection',
            'n8n-nodes-base.httpRequest' => 'httpBasicAuth',
            "n8n-nodes-base.smtp" => 'smtp',
            "n8n-nodes-base.imapEmail" => 'imap',
            "n8n-nodes-base.microsoftTeams" => 'microsoftTeamsOAuth2Api',
            "n8n-nodes-base.facebook" => 'facebookGraphApi',
            "n8n-nodes-base.jira" => 'jiraOAuth2Api',
            "n8n-nodes-base.linkedin" => 'linkedInOAuth2Api',
            "n8n-nodes-base.shopify" => 'shopifyApi',
            "n8n-nodes-base.wordpress" => 'wordpressApi',
            "n8n-nodes-base.zoom" => 'zoomOAuth2Api',
            "n8n-nodes-base.airtable" => 'airtableApi',
            "n8n-nodes-base.telegram" => 'telegramBotApi',
            "n8n-nodes-base.twilio" => 'twilioApi',
            "n8n-nodes-base.stripe" => 'stripeApi',
            "n8n-nodes-base.clickup" => 'clickUpApi',
            "n8n-nodes-base.zendesk" => 'zendeskOAuth2Api',
            "n8n-nodes-base.hubspot" => 'hubspotOAuth2Api',
            "n8n-nodes-base.salesloft" => 'salesloftApi',
            "n8n-nodes-base.microsoftSharepoint" => 'microsoftSharepointOAuth2Api',
            "n8n-nodes-base.amazonS3" => 'amazonS3',
            "n8n-nodes-base.googleCloudStorage" => 'googleCloudStorageOAuth2',
            "n8n-nodes-base.azureBlobStorage" => 'azureBlobStorage',
            "n8n-nodes-base.rabbitmq" => 'rabbitMQConnection',
            "n8n-nodes-base.redis" => 'redisConnection',
            "n8n-nodes-base.kafka" => 'kafkaConnection',
            "n8n-nodes-base.microsoftDynamicsCrm" => 'microsoftDynamicsCrmOAuth2Api',
            "n8n-nodes-base.snowflake" => 'snowflakeConnection',
            "n8n-nodes-base.bigquery" => 'bigQueryOAuth2',
            "n8n-nodes-base.wordpressCom" => 'wordpressComOAuth2Api',
            "n8n-nodes-base.salesforceMarketingCloud" => 'salesforceMarketingCloudApi',
            "n8n-nodes-base.microsoftPowerAutomate" => 'microsoftPowerAutomateOAuth2Api',
            "n8n-nodes-base.googleAds" => 'googleAdsOAuth2',
            "n8n-nodes-base.adobeCreativeCloud" => 'adobeCreativeCloudOAuth2Api',
            "n8n-nodes-base.shopifyAdminApi" => 'shopifyAdminApiOAuth2Api',
            "n8n-nodes-base.facebookMarketing" => 'facebookMarketingApi',
            "n8n-nodes-base.twitterV2" => 'twitterOAuth2Api',
            "n8n-nodes-base.instagram" => 'instagramOAuth2Api',
            "n8n-nodes-base.pipedrive" => 'pipedriveApi',
            "n8n-nodes-base.gitlab" => 'gitlabOAuth2Api',
            "n8n-nodes-base.bitbucket" => 'bitbucketOAuth2Api',
            "n8n-nodes-base.cloudflare" => 'cloudflareApi',
            "n8n-nodes-base.cloudflareWorkers" => 'cloudflareWorkersApi',
            "n8n-nodes-base.cloudflarePages" => 'cloudflarePagesApi',
            "n8n-nodes-base.cloudflareR2" => 'cloudflareR2Api',
            "n8n-nodes-base.spotify" => 'spotifyOAuth2Api',
            "n8n-nodes-base.githubEnterprise" => 'githubEnterpriseOAuth2Api',
            "n8n-nodes-base.amazonAdvertising" => 'amazonAdvertisingApi',
            "n8n-nodes-base.linkedinMarketing" => 'linkedInMarketingApi',
            "n8n-nodes-base.youtube" => 'youTubeOAuth2Api',
            "n8n-nodes-base.reddit" => 'redditOAuth2Api',
            "n8n-nodes-base.twitch" => 'twitchOAuth2Api',
            "n8n-nodes-base.shopware" => 'shopwareApi',
            "n8n-nodes-base.zapier" => 'zapierOAuth2Api',
            "n8n-nodes-base.cloudflareStream" => 'cloudflareStreamApi',
            "n8n-nodes-base.twitterAds" => 'twitterAdsApi',
            "n8n-nodes-base.googleCalendar" => 'googleCalendarOAuth2',
            "n8n-nodes-base.microsoftCalendar" => 'microsoftCalendarOAuth2Api',
            "n8n-nodes-base.facebookPages" => 'facebookPagesApi',
            "n8n-nodes-base.instagramGraphApi" => 'instagramGraphApiOAuth2Api',
            "n8n-nodes-base.microsoftToDo" => 'microsoftToDoOAuth2Api',
            "n8n-nodes-base.microsoftPlanner" => 'microsoftPlannerOAuth2Api',
            "n8n-nodes-base.microsoftOneNote" => 'microsoftOneNoteOAuth2Api',
            "n8n-nodes-base.microsoftWordOnline" => 'microsoftWordOnlineOAuth2Api',
            "n8n-nodes-base.microsoftExcelOnline" => 'microsoftExcelOnlineOAuth2Api',
            "n8n-nodes-base.microsoftPowerPointOnline" => 'microsoftPowerPointOnlineOAuth2Api',
            "n8n-nodes-base.microsoftForms" => 'microsoftFormsOAuth2Api',
            "n8n-nodes-base.microsoftSway" => 'microsoftSwayOAuth2Api',
            "n8n-nodes-base.microsoftYammer" => 'microsoftYammerOAuth2Api',
            "n8n-nodes-base.microsoftStream" => 'microsoftStreamOAuth2Api',
        ];

        return $map[$nodeType] ?? null;
    }
}
