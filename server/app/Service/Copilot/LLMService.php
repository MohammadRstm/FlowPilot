<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

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

        Log::debug('Generating answer with LLM', ['question' => $question, 'topFlows' => $topFlows]);

        $context = self::buildContext($topFlows);

        $prompt = Prompts::getWorkflowGenerationPrompt($question, $context);

        /** @var Response $response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->timeout(90)
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4.1-mini",
                "temperature" => 0.2,
                "messages" => [
                    ["role" => "system", "content" => Prompts::getWorkflowGenerationSystemPrompt()],
                    ["role" => "user", "content" => $prompt]
                ]
            ]);
        
        $resultedFlow = $response->json("choices.0.message.content");
        Log::debug('Generated answer from LLM', ['resultedFlow' => $resultedFlow]);

        return $resultedFlow;
    }

    private static function buildContext(array $flows): string{
        $out = "";
        $counter = 1;

        foreach ($flows as $flow) {

            // if this is a Qdrant result, extract payload
            if (isset($flow["payload"])) {
                $flow = $flow["payload"];
            }

            // if it's not an array now, skip it
            if (!is_array($flow)) {
                continue;
            }

            $name  = $flow["workflow"] ?? "Unknown Workflow";
            $nodes = $flow["nodes_used"] ?? [];
            $count = $flow["node_count"] ?? count($nodes);
            $raw   = $flow["raw"] ?? $flow;

            $out .= "\n--- Workflow {$counter} ---\n";
            $out .= "Name: {$name}\n";
            $out .= "Nodes: " . implode(", ", $nodes) . "\n";
            $out .= "Node Count: {$count}\n";
            $out .= "JSON:\n" . json_encode($raw, JSON_PRETTY_PRINT) . "\n";

            $counter++;
        }

        return $out;
    }

    public static function repairWorkflow(string $badJson, array $errors){
        $prompt = Prompts::getRepairPrompt($badJson, json_encode($errors));

        /** @var Response $response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4.1-mini",
                "temperature" => 0,
                "messages" => [
                    ["role"=>"system","content"=>"You are an n8n validation engine"],
                    ["role"=>"user","content"=>$prompt]
                ]
            ]);
        
        $repairedFlow = $response->json("choices.0.message.content");
        Log::alert('Repaired workflow from LLM', ['repairedFlow' => $repairedFlow]);
        
        return $repairedFlow;
    }

    public static function judgeResults(array $workflow, string $question){
        $prompt = Prompts::getJudgementPrompt($workflow, $question);

        /** @var Response $response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4.1-mini",
                "temperature" => 0,
                "messages" => [
                    ["role"=>"system","content"=>"You are an n8n analysis engine"],
                    ["role"=>"user","content"=>$prompt]
                ]
            ]);
        
        $content = $response->json("choices.0.message.content");

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Failed to decode judgement response: " . json_last_error_msg());
        }

        return $decoded;
    }

}
