<?php

namespace App\Service\Copilot;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class LLMService{

    /** USER QUESTION ANALYZER */
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
            throw new Exception("Prompt injection detected");
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
        $final["nodes"] = AnalyzeIntent::normalizeNodes($final["nodes"]);

        $final["embedding_query"] = AnalyzeIntent::buildWorkflowEmbeddingQuery($final,$question);

        Log::info("Embedding query" , ["query" => $final["embedding_query"]]);

        return $final;
    }

    /** WORKFLOW GENERATION (CORE) */

    public static function generateAnswer(string $question, array $topFlows ,?callable $stage ,  ?callable $trace) {
        $stage("generating");

        $context = self::buildContext($topFlows);
        $planningPrompt = Prompts::getWorkflowBuildingPlanPrompt($question, $context);

        $plan = self::callOpenAI($planningPrompt);

        $trace("genration_plan", [
            "connected_nodes" => $plan["nodes"]
        ]);

        // generate workflow
        $compilerPrompt = Prompts::getWorkflowBuildingPrompt($question , $plan , $context);

        $workflow = self::callOpenAI($compilerPrompt);

        $trace("workflow", [
            "workflow" => $workflow
        ]);

        return $workflow;   
    }

    public static function extractAllowedNodes(array $topFlows): array {
        $set = [];

        foreach ($topFlows as $flow) {
            if (isset($flow["payload"])) {
                $flow = $flow["payload"];
            }

            foreach (($flow["nodes_used"] ?? []) as $n) {
                $set[self::normalizeNodeName($n)] = true;
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

    /** WORKFLOW LOGIC VALIDATOR/JUDGER */
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

    /** WORKFLOW DATA INJECTION/VALIDATION */
    public static function validateDataFlow($workflow , $question , $totalPoints){
        // create data graph
        $dataGraph = self::callOpenAI(
                Prompts::getDataGraphBuilderPrompt(
                    json_encode($workflow, JSON_UNESCAPED_SLASHES),
                    json_encode($totalPoints, JSON_UNESCAPED_SLASHES)
                )
            );

        // resolve references
        $references = self::callOpenAI(
            Prompts::getReferenceResolverPrompt(
                json_encode($workflow, JSON_UNESCAPED_SLASHES),
                json_encode($dataGraph, JSON_UNESCAPED_SLASHES)
            )
        );

        // validate schema
        $schemaErrors = self::callOpenAI(
            Prompts::getSchemaValidatorPrompt(
                json_encode($workflow, JSON_UNESCAPED_SLASHES),
                json_encode($dataGraph, JSON_UNESCAPED_SLASHES),
                json_encode($totalPoints, JSON_UNESCAPED_SLASHES)
            )
        );

        // build execution graph
        $paths = self::callOpenAI(
            Prompts::getExecutionGraphBuilderPrompt(
                json_encode($workflow, JSON_UNESCAPED_SLASHES)
            )
        );

        //  branch & loop safety
        $controlFlowIssues = self::callOpenAI(
            Prompts::getBranchAndLoopSafetyPrompt(
                json_encode($paths, JSON_UNESCAPED_SLASHES)
            )
        );

        // intent validator
        $intentErrors = self::callOpenAI(
            Prompts::getIntentValidatorPrompt(
                $question,
                json_encode($paths, JSON_UNESCAPED_SLASHES),
                json_encode($dataGraph, JSON_UNESCAPED_SLASHES)
            )
        );

        // aggregate errors & score
        return self::callOpenAI(
            Prompts::getErrorAggragatorAndScorerPrompt(
                json_encode($references, JSON_UNESCAPED_SLASHES),
                json_encode($schemaErrors, JSON_UNESCAPED_SLASHES),
                json_encode($controlFlowIssues, JSON_UNESCAPED_SLASHES),
                json_encode($intentErrors, JSON_UNESCAPED_SLASHES)
            )
        );
    }

    public static function repairWorkflowDataFlow(string $question , string $workflow , array $errors , array $totalPoints){
        // repair planner
        $patchPlan = self::callOpenAI(
            Prompts::getRepairPlannerPrompt(
                $question,
                json_encode($errors, JSON_UNESCAPED_SLASHES),
                json_encode($totalPoints, JSON_UNESCAPED_SLASHES)
            )
        );

        // patch applier
        return self::callOpenAI(
            Prompts::getPatchApplierPrompt(
                json_encode($workflow, JSON_UNESCAPED_SLASHES),
                json_encode($patchPlan, JSON_UNESCAPED_SLASHES)
            )
        );
    }

    public static function planSSARebind($question , $violations, $ssaTable){
        $prompt = Prompts::getSSADataFlowPompt($question , json_encode($violations) , json_encode($ssaTable));
        return self::callOpenAI($prompt);
    }

    /** WORKFLOW SAVOR */
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

    // helper
    public static function normalizeNodeName(string $name): string {
        $s = strtolower($name);
        $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

}
