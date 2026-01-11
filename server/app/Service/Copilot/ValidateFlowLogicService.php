<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Log;

class ValidateFlowLogicService{

    private array $seenFingerprints;
    private array $scoreHistory;

    private $bestWorkflow;
    private $bestScore;

    private float $scoreThreshold;
    private int $maxRetries;

    public function __construct(){
        $this->seenFingerprints = [];
        $this->scoreHistory = [];
        $this->bestWorkflow = null;
        $this->bestScore = 0;
        $this->scoreThreshold = 0.95;
        $this->maxRetries = 3;
    }

    public function execute($workflow, $question, $totalPoints, $trace ,  $retries = 0){
        $judgement = LLMService::judgeResults($workflow , $question);

        Log::debug('Workflow judgement', [
            'attempt' => $retries,
            'judgement' => $judgement
        ]);

        $trace("judgement" , [
            "retries" => $retries,
            "capabilities" => $judgement["capabilities"],
            "requirements" => $judgement["requirements"],
            "errors" => $judgement["errors"],
            "matches" => count($judgement["matches"])
        ]);

        $this->updateBestWorkflow($workflow, $judgement['score']);

        // check score
        if($judgement['score'] >= $this->scoreThreshold){
            Log::info("Terminal convergence reached");
            return $this->bestWorkflow ?? $workflow;
        }

        // check if we've seen this workflow before
        $fingerprint = $this->fingerprintWorkflow($workflow);
        if ($this->seenFingerprint($fingerprint, $retries)) {
            return $this->bestWorkflow ?? $workflow;
        }

        // add workflow to fingerprints
        $this->updateFingerprint($fingerprint, $judgement);

        // number of max retries reached
        if ($retries >= $this->maxRetries) {
            Log::info("Max retries reached - returning best workflow");
            return $this->bestWorkflow ?? $workflow;
        }

        $repaired = $this->repairWorkflowLogic($question , $workflow, $judgement, $totalPoints);

        $trace("repaired_workflow", [
            "workflow" => $repaired
        ]);

        return $this->execute($repaired, $question, $totalPoints, $retries + 1);
    }

    private function updateBestWorkflow(array $workflow, float $score){
        if ($score > $this->bestScore) {
            $this->bestScore = $score;
            $this->bestWorkflow = $workflow;
            Log::info("New best workflow with score: $score");
        }
    }

    private function semanticNodeKey(array $node): string{
        return hash('sha256', ($node['type'] ?? '') . '|' . ($node['name'] ?? ''));
    }

    private function canonicalizeNode(array $node): array{
        return [
            'key' => $this->semanticNodeKey($node),
            'type' => $node['type'],
            'name' => $node['name'] ?? null
        ];
    }

    private function remapConnections(array $nodes, array $connections): array{
        $map = [];
        foreach ($nodes as $n) {
            $map[$n['name']] = $this->semanticNodeKey($n);
        }

        $edges = [];
        foreach ($connections as $from => $outs) {
            foreach ($outs['main'] ?? [] as $branch) {
                foreach ($branch as $edge) {
                    if (isset($map[$from]) && isset($map[$edge['node']])) {
                        $edges[] = $map[$from] . '->' . $map[$edge['node']];
                    }
                }
            }
        }

        sort($edges);
        return $edges;
    }

    private function fingerprintWorkflow(array $workflow): string{
        $nodes = $workflow['nodes'] ?? [];
        $connections = $workflow['connections'] ?? [];

        $canon = [];
        foreach ($nodes as $n) $canon[] = $this->canonicalizeNode($n);
        usort($canon, fn($a,$b)=>strcmp($a['key'],$b['key']));

        return hash('sha256', json_encode([
            'nodes'=>$canon,
            'edges'=>$this->remapConnections($nodes,$connections)
        ]));
    }

    private function seenFingerprint($fp, $retries): bool{
        if (isset($this->seenFingerprints[$fp])) {
            Log::warning("Oscillation detected", ['iteration'=>$retries]);
            return true;
        }
        return false;
    }

    private function updateFingerprint($fp, $judgement){
        $this->seenFingerprints[$fp] = true;
        $this->scoreHistory[] = $judgement['score'];
    }

    private function repairWorkflowLogic($question , $workflow, $judgement, $totalPoints){
        $repaired = LLMService::repairWorkflowLogic(
            $question,
            json_encode($workflow),
            $judgement,
            $totalPoints
        );

        return $repaired ?: ($this->bestWorkflow ?? $workflow);
    }
}
