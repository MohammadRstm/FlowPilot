<?php

namespace App\Service\Copilot;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Prompts\Prompt;

class LLMService{

    private static function callOpenAI($prompt){
        /** @var Response $response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
                ->timeout(90)
                ->post("https://api.openai.com/v1/chat/completions", [
                    "model" => "gpt-4.1-mini",
                    "temperature" => 0,
                    "messages" => [
                        ["role" => "system", "content" => $prompt["system"]],
                        ["role" => "user", "content" => $prompt["user"]]
                    ]
                ]);
            
        $results = trim($response->json("choices.0.message.content"));
        $decoded = json_decode($results , true);
        if(!is_array($decoded)){
            Log::error("OPENAI FAILED TO RETURN VALID JSON");
            throw new Exception("OPENAI FAILED TO RETURN VALID JSON");
        }

        return $decoded;
    }

    public static function intentAnalyzer(string $question){
        $prompt = Prompts::getAnalysisIntentAndtiggerPrompt($question);

        return self::callOpenAI($prompt);
    }

    public static function nodeAnalyzer(string $question ,string $intent){
        $prompt = Prompts::getAnalysisNodeExtractionPrompt($question , $intent);

        return self::callOpenAI($prompt);
    }
    
    public static function workflowSchemaValidator(array $intentData , array $nodeData){
        $prompt = Prompts::getAnalysisValidationAndPruningPrompt($intentData["trigger"] , json_encode($nodeData["nodes"]));

        return self::callOpenAI($prompt);
    }

    public static function generateAnswer(string $question, array $topFlows) {
        $context = self::buildContext($topFlows);
        $planningPrompt = Prompts::getWorkflowBuildingPlanPrompt($question, $context);

        $allowedNodes = self::extractAllowedNodes($topFlows);

        $maxRetries = 2;
        $attempt = 0;

        $plan = null;
        $validation = null;

        do {
            $attempt++;

            $plan = self::callOpenAI($planningPrompt);
            $validation = PlanValidator::validate($plan, $allowedNodes);

            if ($validation["ok"]) {
                break;
            }

            Log::warning("Plan attempt {$attempt} failed", $validation["errors"]);

            // Prepare repair prompt for next round
            $planningPrompt = Prompts::getPlanRepairPrompt(
                $question,
                $context,
                $plan,
                $validation["errors"]
            );

        } while ($attempt < $maxRetries);

        if (!$validation["ok"]) {
            Log::error("Plan failed after retries", $validation["errors"]);
            throw new Exception("Invalid plan after retries: " . implode(", ", $validation["errors"]));
        }

        // generate workflow
        $compilerPrompt = Prompts::getWorkflowBuildingPrompt($question , $plan , $context);

        $workflow = self::callOpenAI($compilerPrompt);

        return $workflow;   
    }

    public static function extractAllowedNodes(array $topFlows): array {
        $set = [];

        foreach ($topFlows as $flow) {
            if (isset($flow["payload"])) {
                $flow = $flow["payload"];
            }

            foreach (($flow["nodes_used"] ?? []) as $n) {
                $set[strtolower($n)] = true;
            }
        }

        return array_keys($set);
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

    public static function judgeResults(array $workflow, string $question){
        // functionalities
        $reqPrompt = Prompts::getWorkflowFunctionalitiesPrompt($question);
        $requirements = self::callOpenAI($reqPrompt);

        // what workflow actually does
        $capPrompt = Prompts::getWhatWorkflowActuallyDoes($workflow);
        $capabilities = self::callOpenAI($capPrompt);

        // compare intent vs functionality
        $cmpPrompt = Prompts::getCompareIntentVsWorkflow(
            json_encode($requirements),
            json_encode($capabilities)
        );
        $matches = self::callOpenAI($cmpPrompt);

        // classify sevirity of errrors
        $sevPrompt = Prompts::getClassifySevirityPrompt(json_encode($matches));
        $errors = self::callOpenAI($sevPrompt);

        // get final score
        $scorePrompt = Prompts::getWorkflowScore(
            json_encode($errors),
            count($requirements["requirements"] ?? [])
        );
        $score = self::callOpenAI($scorePrompt);

         return [
            "score" => $score["score"] ?? 0.0,
            "requirements" => $requirements["requirements"] ?? [],
            "capabilities" => $capabilities["capabilities"] ?? [],
            "matches" => $matches["matches"] ?? [],
            "errors" => $errors["errors"] ?? [],
            "suggested_improvements" => []
        ];
    }

    public static function repairWorkflowLogic(string $question , string $badJson, array $judgement , array $totalPoints){
        // What is missing 
        $missingPrompt = Prompts::getWorkflowMissingRequirementsPrompt($question , $badJson , $judgement["matches"]);
        $missingRequirements = self::callOpenAI($missingPrompt);

        // fixing plan
        $fixingPlanPrompt = Prompts::getWorkflowFixingPlanPrompt($question , $missingRequirements , $totalPoints , $badJson);
        $fixingPlan = self::callOpenAI($fixingPlanPrompt);

        // patch workflow
        $patchPrompt = Prompts::getApplyPatchPrompt($question , $badJson , $fixingPlan , $missingRequirements);
        return self::callOpenAI($patchPrompt);
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

    public static function generateWorkflowQdrantPayload(string $json , string $question){
        $prompt = Prompts::getWorkflowMetadataPrompt($json , $question);

        /** @var Response response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4.1-mini",
                "temperature" => 0,
                "messages" => [
                    ["role"=>"system","content"=>"You are an n8n workflow analyzer"],
                    ["role"=>"user","content"=>$prompt]
                ]
            ]);
        
       $metaData = trim($response->json("choices.0.message.content"));
        $metaDataDecoded = json_decode($metaData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode metadata JSON', [
                'json' => $metaData,
                'error' => json_last_error_msg()
            ]);
            throw new \RuntimeException('Failed to decode workflow metadata JSON: ' . json_last_error_msg());
        }
        return $metaDataDecoded;
    }

}
