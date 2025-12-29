<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Http;

class LLMService
{
    public static function generateAnswer(string $question, array $topFlows) {

        $context = self::buildContext($topFlows);

        $prompt = self::buildPrompt($question, $context);

        $response = Http::withToken(env("OPENAI_KEY"))
            ->timeout(90)
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4o",
                "temperature" => 0.2,
                "messages" => [
                    ["role" => "system", "content" => self::systemPrompt()],
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

    private static function buildPrompt(string $question, string $context){
        return <<<PROMPT
            USER GOAL:
            $question

            You are given real n8n workflows below.

            Your task:
            1. Understand the user's goal
            2. Compare the workflows
            3. Decide which one best matches
            4. Modify or merge them if needed
            5. Return ONE final n8n workflow JSON

            RULES:
            - Use only nodes that appear in the provided workflows
            - Keep credentials names unchanged
            - Maintain valid n8n format
            - Ensure triggers exist
            - Ensure connections are correct
            - Include error handling if missing

            WORKFLOWS:
            $context

            OUTPUT:
            Return ONLY a valid n8n JSON.
            No explanations.
            No markdown.
            PROMPT;
    }

    public static function repairWorkflow(string $badJson, array $errors){
        $prompt = <<<PROMPT
        The n8n workflow below FAILED during execution.

        ERRORS:
        $errors

        JSON:
        $badJson

        Fix it so that:
        - All nodes run
        - All inputs exist
        - Credentials are preserved
        - Flow is valid

        Return only JSON.
        PROMPT;

        $response = Http::withToken(env("OPENAI_KEY"))
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
