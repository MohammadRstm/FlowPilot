<?php

namespace App\Console\Commands\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class IngestionService{
    public static function buildSparseVector(string $text): array{
        $text = self::normalizeText($text);

        $tokens = preg_split('/[^a-z0-9]+/i', $text);
        $freqs =self::buildFrequencyArray($tokens);
        

        $indices = [];
        $values  = [];

        foreach($freqs as $token => $count){
            $indices[] = crc32($token);
            $values[]  = (float) $count; // raw TF (idf handled by Qdrant)
        }

        return [
            'indices' => $indices,
            'values'  => $values,
        ];
    }

    public static function embed(string $text): array{
        /** @var Response $response */
        $response = Http::withToken(env('OPENAI_API_KEY', ''))
            ->timeout(1000)
            ->post('https://api.openai.com/v1/embeddings', [
                "model" => "text-embedding-3-large",
                "input" => $text
            ]);

        if (!$response->ok()) {
            throw new \RuntimeException("OpenAI embedding failed: " . $response->body());
        }

        $vector = $response->json('data.0.embedding');

        if (count($vector) !== 3072) {
            throw new \RuntimeException("Embedding size mismatch: " . count($vector));
        }

        return $vector;
    }

    /** helpers */
    private function buildFrequencyArray(array $tokens){
        $freqs = [];
        foreach($tokens as $token){
            if (strlen($token) < 2) continue;
            $freqs[$token] = ($freqs[$token] ?? 0) + 1;
        }

        return $freqs;
    }

    private static function normalizeText(string $text): string{
        $text = strtolower($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}