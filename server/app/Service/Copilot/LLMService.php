<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Http;
use Laravel\Prompts\Prompt;
use Illuminate\Http\Client\Response;

class LLMService{

    public static function raw(string $prompt): string{
        /** @var Response $response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->timeout(60)
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4o",
                "temperature" => 0,
                "messages" => [
                    [
                        "role" => "system",
                        "content" => "You are a JSON API. You must return valid JSON and nothing else."
                    ],
                    [
                        "role" => "user",
                        "content" => $prompt
                    ]
                ]
            ]);

        if (!$response->ok()) {
            throw new \RuntimeException("LLM failed: " . $response->body());
        }

        return trim($response->json("choices.0.message.content"));
    }

    public static function generateAnswer(string $question, array $topFlows) {

        $context = self::buildContext($topFlows);

        $prompt = Prompts::getWorkflowGenerationPrompt($question, $context);

        /** @var Response $response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->timeout(90)
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4o",
                "temperature" => 0.2,
                "messages" => [
                    ["role" => "system", "content" => Prompts::getWorkflowGenerationSystemPrompt()],
                    ["role" => "user", "content" => $prompt]
                ]
            ]);

        return $response->json("choices.0.message.content");
    }

    private static function buildContext(array $flows): string {
        $out = "";

        foreach ($flows as $i => $flow) {
            $out .= "\n--- Workflow " . ($i+1) . " ---\n";
            $out .= "Name: {$flow['workflow']}\n";
            $out .= "Nodes: " . implode(", ", $flow["nodes"]) . "\n";
            $out .= "Node Count: {$flow['node_count']}\n";
            $out .= "JSON:\n" . json_encode($flow["raw"], JSON_PRETTY_PRINT) . "\n";
        }

        return $out;
    }

    public static function repairWorkflow(string $badJson, array $errors){
        $prompt = Prompts::getRepairPrompt($badJson, json_encode($errors));

        /** @var Response $response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4o",
                "temperature" => 0,
                "messages" => [
                    ["role"=>"system","content"=>"You are an n8n validation engine"],
                    ["role"=>"user","content"=>$prompt]
                ]
            ]);
        
        return $response->json("choices.0.message.content");
    }

}
