<?php

namespace App\Service\Copilot;

use App\Service\N8N\N8nRunner;
use App\Service\N8N\N8nValidatorService;

class GetAnswer{
    public static function execute($question){

        $analysis = AnalyzeIntent::analyze($question);
        $points = GetPoints::execute($analysis);
        $flows = RankingFlows::rank($analysis, $points);

        $json = LLMService::generateAnswer($question, $flows);
        $workflow = json_decode($json, true);

        // Step 1: Structural validation
        $validation = N8nValidatorService::validate($workflow);

        if (!$validation["valid"]) {
            $json = LLMService::repairWorkflow(
                json_encode($workflow),
                json_encode($validation["errors"])
            );
            $workflow = json_decode($json, true);
        }

        // Step 2: Runtime simulation
        $run = N8nRunner::run($workflow);

        if (!$run["success"]) {
            $json = LLMService::repairWorkflow(
                json_encode($workflow),
                json_encode($run["errors"])
            );
        }

        return $json;
    }
}
