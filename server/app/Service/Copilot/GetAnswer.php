<?php

namespace App\Service\Copilot;

use App\Exceptions\UserFacingException;
use Illuminate\Support\Facades\Log;

// THINGS WE DO THAT DOESN'T MAKE SENSE : 
// IN RAG WE SAVE N8N NODES CATALOGS AND N8N NODES SCHEMAS ALTHOUGH SCHEMAS ALONE MIGHT SUFFICE
// ANALYZE INTENT SERVICE GIVES US THE NODES NEEDED FOR THE WORFKLOW GENERATION, BUT THERE IS NO GURANTEE THAT AN AI MODEL ACTUALLY KNOWS ALL THE N8N NODES AVAILABLE

class GetAnswer{
    // Orchestrater
    public static function execute(array $messages , ?callable $stream = null){
        set_time_limit(300); // 5 minutes max

        // intialize streaming services
        $stage = self::initializeStage($stream);
        $trace = self::initializeTrace($stream);
        $error = self::initializeError($stream);

        try{
            $analysis = AnalyzeIntent::analyze($messages , $stage , $trace);// clean
            $points = GetPoints::execute($analysis , $stage , $trace);// clean
            $finalPoints = RankingFlows::rank($analysis, $points , $stage);// clean
            $workflow = LLMService::generateAnswer($analysis, $finalPoints , $stage , $trace);// clean

            $validateWorkflowService = new ValidateFlowLogicService();
            $workflow = $validateWorkflowService->execute($workflow , $analysis , $finalPoints ,$stage ,  $trace);

            return $workflow;
        }catch(UserFacingException $e){
            $error($e->getMessage());
        }catch(\Throwable $e){
            Log::error('Copilot execution failed', [
                'exception' => $e,
                'stage' => 'execute',
            ]);

            $error("Unexpected error occurred");

            return null;
        }
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

    
    private static function initializeError($stream) {
        return fn(string $message, array $context = [], int $code = 400) 
            => $stream && $stream("error", [
                "message" => $message,
                "code" => $code,
                "context" => $context,
            ]);
    }
}




