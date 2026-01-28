<?php

namespace App\Service\Copilot;

use App\Exceptions\UserFacingException;
use App\Service\Copilot\Service\AnalyzeIntent;
use App\Service\Copilot\Service\GetPoints;
use App\Service\Copilot\Service\LLMService;
use App\Service\Copilot\Service\RankingFlows;
use App\Service\Copilot\Service\ValidateFlowLogicService;
use Illuminate\Support\Facades\Log;

class GetAnswer{
    // Orchestrater
    public static function execute(array $messages , ?callable $stream = null){
        set_time_limit(300); // 5 minutes max

        // intialize streaming services
        $stage = self::initializeStage($stream);
        $trace = self::initializeTrace($stream);
        $error = self::initializeError($stream);

        try{
            $analysis = AnalyzeIntent::analyze($messages , $stage , $trace);
            $points = GetPoints::execute($analysis , $stage , $trace);
            $finalPoints = RankingFlows::rank($analysis, $points , $stage);
            $workflow = LLMService::generateAnswer($analysis, $finalPoints , $stage , $trace);

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

    public static function initializeTrace($stream){
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




