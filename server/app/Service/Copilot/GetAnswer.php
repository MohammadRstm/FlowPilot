<?php

namespace App\Service\Copilot;

use App\Service\N8N\CredentialsInjecterService;
use App\Service\N8N\N8nRunner;
use App\Service\N8N\N8nValidatorService;
use Illuminate\Support\Facades\Log;

class GetAnswer{
    // Orchestrater
    public static function execute($question , $user = null){

        $analysis = AnalyzeIntent::analyze($question);
        $points = GetPoints::execute($analysis);
        $totalPoints = RankingFlows::rank($analysis, $points);

        $json = LLMService::generateAnswer($question, $totalPoints);
        /** @var array|string|false $workflowDecoded */
        $workflowDecoded = json_decode($json, true);
        if (!is_array($workflowDecoded)) {
            Log::error('LLM returned invalid JSON', ['json' => $json]);
            return $json;
        }

        /** @var array $workflow */
        $workflow = $workflowDecoded;

        // analyze output workflow with user's intent (sanity check)
                
        if($user["n8n_url"] && $user["n8n_api_key"]){// if user has his account connected validate + run workflow
          $json = self::validateWorkflow($workflow , $user);
        }

        return $json;
    }

    private static function validateWorkflow($workflow , $user){
        // inject credentials
        $workflow = CredentialsInjecterService::inject($workflow, $user);

        $validation = N8nValidatorService::validate($workflow , $user);

        self::handleValidationErrors($validation , $workflow);

        $run = N8nRunner::run($workflow , $user);
        if($run["output"]){// silently ignore incapable runs
            self::handleRunErrors($run , $workflow);
        }
        // return final workflow JSON string
        return json_encode($workflow);
    }

    private static function handleValidationErrors($validation , &$workflow){
        if(!$validation["valid"]){
            $validationErrors = $validation["errors"] ?? [];
            if(!is_array($validationErrors)){
                $validationErrors = [$validationErrors];
            }

            $json = LLMService::repairWorkflow(
                json_encode($workflow),
                $validationErrors
            );

            /** @var array|string|false $repairedDecoded */
            $repairedDecoded = json_decode($json, true);
            if (!is_array($repairedDecoded)) {
                Log::error('RepairWorkflow returned invalid JSON', ['json' => $json]);
                return $json;
            }

            /** @var array $workflow */
            $workflow = $repairedDecoded;
        }
    }

    private static function handleRunErrors($run , &$workflow){
        if (!$run["success"]) {
            $runErrors = $run["errors"] ?? [];
            if(!is_array($runErrors)){
                $runErrors = [$runErrors];
            }

            $json = LLMService::repairWorkflow(
                json_encode($workflow),
                $runErrors
            );

            /** @var array|string|false $repairedDecoded */
            $repairedDecoded = json_decode($json, true);
            if (!is_array($repairedDecoded)) {
                Log::error('RepairWorkflow returned invalid JSON', ['json' => $json]);
                return $json;
            }

            /** @var array $workflow */
            $workflow = $repairedDecoded;
        }
    }
}
