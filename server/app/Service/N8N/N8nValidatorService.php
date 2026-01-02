<?php

namespace App\Service\N8N;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class N8nValidatorService{
    public static function validate(array $workflow , array $user): array {
        $url = $user["n8n_url"] . "/rest/workflows/validate";
      
        /** @var array|null $errorsJson */
        $errorsJson = self::getErrors($user, $workflow, $url);

        if (is_array($errorsJson) && array_key_exists('valid', $errorsJson)) {
            return $errorsJson;
        }

        return [
            'valid' => false,
            'errors' => $errorsJson
        ];
    }

    private static function getErrors($user, $workflow, $url){
        /** @var Response $response */
        $response = Http::withHeader([
            "X-N8N-API-KEY" => $user["n8n_api_key"],
            "Content-type" => "application/json"
            ])->post($url, $workflow);

        if ($response->ok()) {
            return [
                "valid" => true,
                "errors" => []
            ];
        }

        return $response->json();

    }
}
