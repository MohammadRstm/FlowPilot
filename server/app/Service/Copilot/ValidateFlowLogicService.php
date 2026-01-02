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

    private bool $criticalEverSeen = false;
    private array $unresolvedCriticalFingerprints = [];


    public function __construct(){
        $this->seenFingerprints = [];
        $this->scoreHistory = [];
        $this->bestWorkflow = null;
        $this->bestScore = 0;
        $this->scoreThreshold = 0.90;
        $this->maxRetries = 3;
        $this->criticalEverSeen = false;
    }

    public function execute($workflow, $question, $totalPoints, $retries = 0){

        $judgement = LLMService::judgeResults($workflow, $question);

        Log::debug('Workflow judgement', [
            'attempt' => $retries,
            'judgement' => $judgement
        ]);

        $this->updateBestWorkflow($workflow, $judgement['score']);

        if ($this->hasCritical($judgement)) {
            $this->criticalEverSeen = true;
            $this->unresolvedCriticalFingerprints[] = $this->fingerprintWorkflow($workflow);
        }

        if (
            $judgement['score'] >= $this->scoreThreshold &&
            !$this->hasCriticalOrMajor($judgement) &&
            !$this->criticalEverSeenUnfixed($workflow)
        ) {
            Log::info("Terminal convergence reached");
            return $this->bestWorkflow ?? $workflow;
        }


        $fingerprint = $this->fingerprintWorkflow($workflow);
        if ($this->seenFingerprint($fingerprint, $retries)) {
            return $this->bestWorkflow ?? $workflow;
        }

        $this->updateFingerprint($fingerprint, $judgement);

        if (!$this->gettingBetterScore()) {
            return $this->bestWorkflow ?? $workflow;
        }

        if (!$this->hasCriticalOrMajor($judgement) && $retries >= $this->maxRetries) {
            Log::info("Max retries reached — returning best workflow");
            return $this->bestWorkflow ?? $workflow;
        }

        $repaired = $this->repairWorkflowLogic($workflow, $judgement, $totalPoints);

        $fp = $this->fingerprintWorkflow($repaired);
        $this->unresolvedCriticalFingerprints = array_filter(
            $this->unresolvedCriticalFingerprints,
            fn($old) => $old !== $fp
        );


        return $this->execute($repaired, $question, $totalPoints, $retries + 1);
    }

    private function hasCritical(array $judgement): bool {
        foreach ($judgement['errors'] as $e) {
            if ($e['severity'] === 'critical') return true;
        }
        return false;
    }


    private function hasCriticalOrMajor(array $judgement): bool {
        foreach ($judgement['errors'] as $e) {
            if (in_array($e['severity'], ['critical','major'])) return true;
        }
        return false;
    }

    private function criticalEverSeenUnfixed(array $workflow): bool {
        if (!$this->criticalEverSeen) return false;

        $fp = $this->fingerprintWorkflow($workflow);

        return in_array($fp, $this->unresolvedCriticalFingerprints, true);
    }


    private function updateBestWorkflow(array $workflow, float $score){
        if ($score > $this->bestScore) {
            $this->bestScore = $score;
            $this->bestWorkflow = $workflow;
            Log::info("New best workflow with score: $score");
        }
    }

    private function gettingBetterScore(): bool{
        if (count($this->scoreHistory) >= 3) {
            $last = array_slice($this->scoreHistory, -3);
            if (max($last) - min($last) < 0.03) {
                Log::warning("Stagnation detected", $last);
                return false;
            }
        }
        return true;
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

    private function repairWorkflowLogic($workflow, $judgement, $totalPoints){
        $buckets = $this->weightErrors($judgement);

        // NEVER FIX MINOR AFTER CORE IS VALID
        if (!empty($buckets['critical'])) {
            $targets = $buckets['critical'];
        }
        elseif (!empty($buckets['major'])) {
            $targets = $buckets['major'];
        }
        else {
            Log::info("Only minor issues remain — stopping repair");
            return $this->bestWorkflow ?? $workflow;
        }

        $repaired = LLMService::repairWorkflowLogic(
            json_encode($workflow),
            $targets,
            $totalPoints
        );

        return json_decode($repaired, true) ?: ($this->bestWorkflow ?? $workflow);
    }

    private function weightErrors(array $judgement): array {
        $buckets = ['critical'=>[], 'major'=>[], 'minor'=>[]];

        foreach ($judgement['errors'] as $e) {
            $buckets[$e['severity']][] = $e['message'];
        }

        foreach ($judgement['suggested_improvements'] as $e) {
            $buckets[$e['severity']][] = $e['message'];
        }

        return $buckets;
    }
}
