<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Log;

class ValidateFlowLogicService{

    private array $seenFingerprints;
    private array $scoreHistory;

    private $bestWorkflow;
    private $bestScore;

    private $socreThreshold;
    private $maxRetries;

    public function __construct(){
        $this->seenFingerprints = [];
        $this->scoreHistory = [];
        $this->bestWorkflow = null;
        $this->bestScore = 0;
        $this->socreThreshold = 0.90;
        $this->maxRetries = 3;
    }



    public function execute($workflow, $question, $retries = 0){

        $judgement = LLMService::judgeResults($workflow, $question);
        Log::debug('Workflow judgement', ['attempt' => $retries, 'judgement' => $judgement]);

        if ($judgement['score'] > self::$bestScore) {
            self::$bestScore = $judgement['score'];
            self::$bestWorkflow = $workflow;
        }

        $fingerprint = self::fingerprintWorkflow($workflow);

        if(self::seenFingerprint($fingerprint, $retries)){
            return self::$bestWorkflow ?? $workflow; // stop the loop because we are oscillating
        }
        
        self::updateFingerprint($fingerprint, $judgement);

        if(!self::gettingBetterScore($workflow)){
            return self::$bestWorkflow ?? $workflow; // stop the loop because we are not improving
        }

        if(self::reachedScoreThreshold($judgement)){
            return self::$bestWorkflow ?? $workflow; // converged
        }

        if (self::reachedMaxRetries($retries)) {
            return self::$bestWorkflow ?? $workflow; // stop infinite loops
        }

        $repaired = self::repairWorkflowLogic($workflow, $judgement);

        return self::execute($repaired, $question, $retries + 1);
    }
 
    private function fingerprintWorkflow(array $workflow): string{
        $nodes = $workflow['nodes'] ?? [];
        $connections = $workflow['connections'] ?? [];

        $summary = [];

        foreach ($connections as $from => $outs) {
            foreach ($outs['main'] ?? [] as $branch) {
                foreach ($branch as $edge) {
                    $summary[] = $from . '->' . $edge['node'];
                }
            }
        }

        sort($summary); // order-independent

        return hash('sha256', implode('|', $summary));
    }

    private function seenFingerprint($fingerprint, $retries): bool{
        if (isset(self::$seenFingerprints[$fingerprint])) {
            Log::warning("Oscillation detected", [
                "iteration" => $retries,
                "fingerprint" => $fingerprint
            ]);
            return true; // stop the loop because we are oscillating
        }
        return false;
    }

    private function updateFingerprint($fingerprint, $judgement){
        self::$seenFingerprints[$fingerprint] = true;
        self::$scoreHistory[] = $judgement['score'];
    }

    private function validateRepairedJson($repaired) {
        if (!is_array($repaired)) {
            $repaired = trim($repaired); 
            // incase LLM added extra text
            $firstBrace = strpos($repaired, '{');
            $lastBrace = strrpos($repaired, '}');
            if ($firstBrace !== false && $lastBrace !== false) {
                $repaired = substr($repaired, $firstBrace, $lastBrace - $firstBrace + 1);
                $repaired = json_decode($repaired, true);
            }
        }

        return $repaired;
    }

    private function gettingBetterScore($workflow){
         if (count(self::$scoreHistory) >= 3) {
            $last = array_slice(self::$scoreHistory, -3);
            if (max($last) - min($last) < 0.03) {
                Log::warning("Stagnation detected", $last);
                return false; // we are not getting better, no need to burn tokens
            }
        }
        return true;
    }

    private function reachedScoreThreshold($judgement){
        if ($judgement["score"] >= self::$socreThreshold) {
            return true;
        }
        return false;
    }

    private function reachedMaxRetries($retries){
        if ($retries >= self::$maxRetries) {
            return true;
        }
        return false;
    }   

    private function repairWorkflowLogic($workflow, $judgement){
        $repaired = json_decode(
            LLMService::repairWorkflow(
                json_encode($workflow),
                array_merge($judgement["errors"], $judgement["suggested_improvements"])
            ),
            true
        );

        $repaired = self::validateRepairedJson($repaired);
        
        if (!is_array($repaired)) {
            Log::error("Repair failed — keeping last valid workflow");
            return self::$bestWorkflow ?? $workflow;
        }

        return $repaired;
    }


}
