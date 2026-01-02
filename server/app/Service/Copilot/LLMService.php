<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Prompts\Prompt;

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

        Log::info('Generating answer with LLM');

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

        Log::info('Generated answer from LLM', ['resultedFlow' => $resultedFlow]);

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

    public static function repairWorkflowLogic(string $badJson, array $errors , array $totalPoints){
        $prompt = Prompts::getRepairWorkflowLogic($badJson, json_encode($errors) , json_encode($totalPoints));

        /** @var Response response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4.1-mini",
                "temperature" => 0,
                "messages" => [
                    ["role"=>"system","content"=>"You are an n8n workflow logic validation engine"],
                    ["role"=>"user","content"=>$prompt]
                ]
            ]);
        
        $repairedFlow = $response->json("choices.0.message.content");
        
        return $repairedFlow;
    }
    
    public static function validateDataFlow($workflow , $question , $totalPoints){
        $prompt = Prompts::getCompleteDataFlowValidationPrompt($workflow , $question , $totalPoints);

        /** @var Response response */
        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4.1-mini",
                "temperature" => 0,
                "messages" => [
                    ["role"=>"system","content"=>"YOU ARE AN EXPERT N8N DATA FLOW VALIDATOR.YOUR MAIN PROITRITY IS TO MAKE SURE DATA FLOW IN THE GIVEN N8N WORKFLOW IS CORRECT AND ADHERS TO THE USER'S INTENT."],
                    ["role"=>"user","content"=>$prompt]
                ]
            ]);

        if(!$response->ok()){
            throw new \RuntimeException("Failed to get data flow validatoin openAI response");
        }

        $content = $response->json("choices.0.message.content");
        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Failed to decode data flow analysis response: " . json_last_error_msg());
        }
        return $decoded;   
    }

    public static function repairWorkflowDataFlow(string $badJson , array $errors , array $totalPoints){
        $prompt = Prompts::getRepairWorkflowDataFlowLogic($badJson, json_encode($errors) , json_encode($totalPoints));

        /** @var Response response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4.1-mini",
                "temperature" => 0,
                "messages" => [
                    ["role"=>"system","content"=>"You are an n8n workflow data flow engine"],
                    ["role"=>"user","content"=>$prompt]
                ]
            ]);
        
        $repairedFlow = $response->json("choices.0.message.content");
        
        return $repairedFlow;
    }

}
