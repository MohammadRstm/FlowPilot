<?php

namespace App\Service\Copilot;

class GetAnswer{
    public static function execute($question){
        $analysis = AnalyzeIntent::analyze($question);
        $result =  GetPoints::execute($analysis);
        $topWorkFlows = RankingFlows::rank([], $result);
        $answer = LLMService::generateAnswer($question, $topWorkFlows);
        return $answer;
    }
}
