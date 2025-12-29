<?php

namespace App\Service\Copilot;

use App\Service\N8nValidatorService;

class GetAnswer{
      public static function execute($question){

        $analysis = AnalyzeIntent::analyze($question);

        $points = GetPoints::execute($analysis);

        $flows = RankingFlows::rank($analysis, $points);

        $json = LLMService::generateAnswer($question, $flows);

        $workflow = json_decode($json, true);

        $validation = N8nValidatorService::validate($workflow);

        if (!$validation["valid"]) {
            $fixed = LLMService::repairWorkflow(
                json_encode($workflow),
                json_encode($validation["errors"])
            );

            return $fixed;
        }

        return $json;
    }
}
