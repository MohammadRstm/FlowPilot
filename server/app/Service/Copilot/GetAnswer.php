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
// WE NEED TO SAVE WORKFLOWS THAT GET 100% IN RAG SYSTEM (BETTER YET ONLY SAVE IF USER APPROVES ON FLOW)
// WE NEED TO FIND NEW SOURCES FOR BETTER RAG INJECTION
// WE MUST ADD THE ABILITY FOR USERS TO CONTINUE THE CONVERSATION:
// --> users might receive a workflow deemed functional by our system
// --> users might want to edit the workflow so they send another message
// --> we must hold the previously generated workflow and work on fixing it according to user's needs
// I might need to implement some of the frontend for this to make sense

// THINGS WE DO THAT DOESN'T MAKE SENSE : 
// WHEN A WORKFLOW IS DEEMED SUITABLE WE ONLY GIVE THE AI MODEL THE WORKFLOW DISCARDING THE N8N NODES AND SCHEMAS, THIS MIGHT CAUSE HALLUSCINATIONS INCASE THE AI WANTED TO ADD NEW NODES
// IN RAG WE SAVE N8N NODES CATALOGS AND N8N NODES SCHEMAS ALTHOUGH SCHEMAS ALONE MIGHT SUFFICE
// IN RAG WE HAVE NO CHUNKING
// ANALYZE INTENT SERVICE GIVES US THE NODES NEEDED FOR THE WORFKLOW GENERATION, BUT THERE IS NO GURANTEE THAT AN AI MODEL ACTUALLY KNOWS ALL THE N8N NODES AVAILABLE



class GetAnswer{
    // Orchestrater
    public static function execute(array $messages , ?callable $stream = null){
        set_time_limit(300); // 5 minutes max

        $stage = fn($name) => $stream && $stream("stage", $name);
        $trace = fn($type, $payload) => $stream && $stream("trace", [
            "type" => $type,
            "payload" => $payload
        ]);

        $stage("analyzing");

        $analysis = AnalyzeIntent::analyze($messages);// optimized
        $question = $analysis["question"];
 
        $trace("analyzing", [
            "intent" => $analysis["intent"],
        ]);

        $points = GetPoints::execute($analysis);// optimized --> requires re-injection

        $trace("retrieval", [
            "candidates" => [
                "workflows_count" => count($points["workflows"]),
                "nodes" => $points["nodes"],
            ]
        ]);

        $finalPoints = RankingFlows::rank($analysis, $points , $stage);

        $workflow = LLMService::generateAnswer($question, $finalPoints , $trace);// optimized

        $trace("generating", [
            "workflow" => $workflow
        ]);

        $stage("validating");

        // analyze output workflow with user's intent 
        $validateWorkflowService = new ValidateFlowLogicService();// optimized -> requires prompt sharpening
        $workflow = $validateWorkflowService->execute($workflow , $question , $finalPoints , $trace);

        // $validateWorkflowDataInjection = new ValidateFlowDataInjection();// we are here now
        // $workflow = $validateWorkflowDataInjection->execute($workflow , $question , $finalPoints);

        return $workflow;
    }
}



