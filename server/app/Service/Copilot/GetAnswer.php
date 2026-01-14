<?php

namespace App\Service\Copilot;
use Illuminate\Support\Facades\Log;
// CURRENT ISSUES TO FIX :

// N8N generation hallucinates with complex requests
// Generation of non existing nodes (rare but happens)
// Generation of old nodes or nodes no longer supported
// Ranking system almost always declines workflows retrieved from RAG
// We are alwasy getting the limit number of workflows from RAG which means filtering and RAG intent interpitation are wrong
// Injecting unsupported options/parameters (rare but happens)

// FEATURES YET TO BE DONE :
// INHANCE THE ABILITY TO HOLD A CONVERSATION

// THINGS WE DO THAT DOESN'T MAKE SENSE : 
// IN RAG WE SAVE N8N NODES CATALOGS AND N8N NODES SCHEMAS ALTHOUGH SCHEMAS ALONE MIGHT SUFFICE
// ANALYZE INTENT SERVICE GIVES US THE NODES NEEDED FOR THE WORFKLOW GENERATION, BUT THERE IS NO GURANTEE THAT AN AI MODEL ACTUALLY KNOWS ALL THE N8N NODES AVAILABLE

// nlp 
class GetAnswer{
    // Orchestrater
    public static function execute(array $messages , ?callable $stream = null){
        set_time_limit(300); // 5 minutes max

        // streaming services
        $stage = self::initializeStage($stream);
        $trace = self::initializeTrace($stream);

        $analysis = AnalyzeIntent::analyze($messages , $stage , $trace);
        $question = $analysis["question"];

        $points = GetPoints::execute($analysis , $stage , $trace);
        $finalPoints = RankingFlows::rank($analysis, $points , $stage);
        $workflow = LLMService::generateAnswer($question, $finalPoints , $stage , $trace);

        $validateWorkflowService = new ValidateFlowLogicService();
        $workflow = $validateWorkflowService->execute($workflow , $question , $finalPoints ,$stage ,  $trace);

        return $workflow;
    }

    private static function initializeStage($stream){
        return fn($name) => $stream && $stream("stage", $name);// shorthand (sends chunks were events = 'stage' and payload is the name of the event)
    }

    private static function initializeTrace($stream){
        return fn($type, $payload) => $stream && $stream("trace", [// shortand (sends more complex chunks where payload can be anything and events hold the type of the event themselves)
            "type" => $type,
            "payload" => $payload
        ]);
    }
}



