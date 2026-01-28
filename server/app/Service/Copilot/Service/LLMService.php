<?php

namespace App\Service\Copilot\Service;

use App\Exceptions\UserFacingException;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class LLMService{

    private static function callOpenAI($prompt){
        if(!$prompt || !$prompt["system"] || !$prompt["user"]) throw new Exception("Invalid prompt received");

        $model = env("OPENAI_MODEL");

        /** @var Response $response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
                ->timeout(90)
                ->post("https://api.openai.com/v1/chat/completions", [
                    "model" => $model,
                    "temperature" => 0,
                    "messages" => [
                        ["role" => "system", "content" => $prompt["system"]],
                        ["role" => "user", "content" => $prompt["user"]]
                    ]
                ]);
 
        $results = trim($response->json("choices.0.message.content"));
        $decoded = json_decode($results , true);

        if (json_last_error() === JSON_ERROR_NONE && $decoded !== null){
            return $decoded;
        }

        Log::error("RAW CONTENT :" ,  ["content" => $results]);

        if(preg_match('/\{.*\}|\[.*\]/s', $results, $m)){// AI may have included some markdown or explanation
            $candidate = $m[0];
            $decoded2 = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded2 !== null) {
                Log::warning('LLMService: extracted JSON substring from model output');
                return $decoded2;
            }
        }

        Log::error('LLMService: OpenAI returned non-JSON response', [
            'model' => $model,
            'raw' => substr($results, 0, 10000), 
        ]);

        throw new Exception("LLMService: non-JSON response from model (logged raw content).");
    }

    public static function intentAnalyzer(array $messages){

        // prompt-injection/creating a final user message
        $prompt = Prompts::getSecureIntentCompilerPrompt($messages);
        $analyzedQuestion = self::callOpenAI($prompt);

        if($analyzedQuestion["attack"]){
            throw new UserFacingException("Prompt injection detected");
        }else{
            $question = $analyzedQuestion["question"];
        }

        Log::info("Analyzed question : " , ["question" => $question]);

        $prompt = Prompts::getAnalysisIntentAndtiggerPrompt($question);
        $intentData = self::callOpenAI($prompt);

        Log::info("User's intent : " , ["intent" => $intentData["intent"] , "trigger" => $intentData["trigger"]]);

        $prompt = Prompts::getAnalysisNodeExtractionPrompt($intentData["intent"] , $question);
        $nodeData = self::callOpenAI($prompt);

        $prompt = Prompts::getAnalysisValidationAndPruningPrompt($question , $intentData["intent"] , $intentData["trigger"] , json_encode($nodeData["nodes"]));
        $final = self::callOpenAI($prompt);

        Log::info("Extracted Nodes" , ["nodes" => $final["nodes"] , "min_nodes" => $final["min_nodes"]]);
  
        $final["intent"] = $intentData["intent"];
        $final["trigger"] = $intentData["trigger"];
        $final["question"] = $question;


        return $final;
    }

    /** WORKFLOW GENERATION (CORE) */
    public static function generateAnswer(array $analysis, array $finalPoints ,?callable $stage ,  ?callable $trace) {
        $stage("generating");

        $workflowContext = WorkflowGeneration::buildWorkflowContext($finalPoints["workflows"] ?? []);
        $nodesContext = WorkflowGeneration::buildSchemasContext($finalPoints["schemas"]);

        $planningPrompt = Prompts::getWorkflowBuildingPlanPrompt($analysis, $workflowContext);
        $plan = self::callOpenAI($planningPrompt);

        $trace("genration_plan", [
            "connected_nodes" => $plan["nodes"]
        ]);

        // generate workflow
        $compilerPrompt = Prompts::getWorkflowBuildingPrompt($analysis , $plan , $workflowContext ,$nodesContext);
        $workflow = self::callOpenAI($compilerPrompt);

        $trace("workflow", [
            "workflow" => $workflow
        ]);

        return $workflow;   
    }

    /** WORKFLOW LOGIC VALIDATOR/JUDGER */
    public static function judgeResults(array $workflow, array $analysis){
        // functionalities
        $reqPrompt = Prompts::getWorkflowFunctionalitiesPrompt($analysis);
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

    /** WORKFLOW SAVER */
    public static function generateWorkflowQdrantPayload(string $json , string $question){
        $prompt = Prompts::getWorkflowMetadataPrompt($json , $question);

        /** @var Response response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4.1-mini",
                "temperature" => 0,
                "messages" => [
                    ["role"=>"system","content"=>"You are an n8n workflow analyzer, You must return only json data as per the user's request."],
                    ["role"=>"user","content"=>$prompt]
                ]
            ]);
        Log::debug("Workflow metadata response" , ["response" => $response->json()]);
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

    // helper
    public static function normalizeNodeName(string $name): string {
        $s = strtolower($name);
        $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

}
