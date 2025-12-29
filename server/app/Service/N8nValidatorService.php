<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;

class N8nValidatorService{
    public static function validate(array $workflow): array {
        $url = env("N8N_URL") . "/rest/workflows/validate";

        $response = Http::withToken(env("N8N_API_KEY"))
            ->post($url, $workflow);

        if ($response->ok()) {
            return [
                "valid" => true,
                "errors" => []
            ];
        }

        return [
            "valid" => false,
            "errors" => $response->json()
        ];
    }
}
