<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Log;

class ValidateFlowDataInjection{
    private int $maxFixes = 3;
    private float $scoreThreshold = 0.9;

    public function execute(array $workflow, string $question, array $totalPoints): array{
        Log::info("SSA DATA FLOW VALIDATION START");

        $ssa = SSAService::buildSymbolTable($workflow);
        Log::info("BUILT SYMBOL TABLE");

        $executionGraph = SSAService::buildExecutionGraph($workflow);
        Log::info("EXEXUTION GRAPH BUILT");

        $uses = SSAService::extractValueUses($workflow);
        Log::info("EXTRACTED VALUES USED");


        $previousUnresolved = PHP_INT_MAX;

        for ($pass = 1; $pass <= $this->maxFixes; $pass++) {
            Log::info("SSA VALIDATION PASS #{$pass}");

            $violations = SSAService::validateUses(
                $uses,
                $ssa,
                $executionGraph
            );

            $unresolved = count($violations);

            Log::info("SSA violations", [
                'count' => $unresolved,
                'violations' => $violations
            ]);

            $score = 1 - ($unresolved / max(count($uses), 1));
            Log::info("SSA score", ['score' => $score]);

            if ($score >= $this->scoreThreshold || $unresolved === 0) {
                Log::info("SSA validation converged");
                return $workflow;
            }

            if ($unresolved >= $previousUnresolved) {
                Log::warning("No SSA progress — aborting repair loop");
                break;
            }

            $previousUnresolved = $unresolved;

            $phiPlans = SSAService::planPhiNodes($violations);

            if (!empty($phiPlans)) {
                Log::info("Applying SSA φ-nodes", ['count' => count($phiPlans)]);
                $workflow = SSAService::applyPhiNodes($workflow, $phiPlans);
                $uses = SSAService::extractValueUses($workflow);
                continue;
            }

            $patchPlan = LLMService::planSSARebind(
                $question,
                $violations,
                $ssa
            );

            if (empty($patchPlan['patches'])) {
                Log::warning("No valid SSA patches proposed");
                break;
            }

            $validatedPatches = $this->validatePatchPlan(
                $patchPlan,
                $violations
            );

            if (empty($validatedPatches)) {
                Log::warning("All SSA patches rejected by validator");
                break;
            }


            $workflow = SSAService::applyPatches(
                $workflow,
                $patchPlan['patches']
            );

            $uses = SSAService::extractValueUses($workflow);
        }

        Log::info("SSA validation finished — returning last stable workflow");
        return $workflow;
    }

    private function validatePatchPlan(
        array $patchPlan,
        array $violations
    ): array {
        if (!isset($patchPlan['patches']) || !is_array($patchPlan['patches'])) {
            Log::warning("Invalid patch plan shape");
            return [];
        }

        // Build fast lookup of valid bindings by using_node
        $validBindingsIndex = [];

        foreach ($violations as $violation) {
            $useNode = $violation['use']['using_node'];
            $validBindingsIndex[$useNode] = [];

            foreach ($violation['valid_bindings'] as $binding) {
                $validBindingsIndex[$useNode][] =
                    $binding['node'] . '.' . $binding['path'];
            }
        }

        $validated = [];

        foreach ($patchPlan['patches'] as $patch) {
            // ---- schema enforcement ----
            if (
                !is_array($patch) ||
                !isset($patch['node'], $patch['field'], $patch['bind_to']) ||
                !is_string($patch['node']) ||
                !is_string($patch['field']) ||
                !is_string($patch['bind_to'])
            ) {
                Log::warning("Rejected patch — schema violation", [
                    'patch' => $patch
                ]);
                continue;
            }

            $node = $patch['node'];
            $bindTo = $patch['bind_to'];

            // ---- must correspond to a real violation ----
            if (!isset($validBindingsIndex[$node])) {
                Log::warning("Rejected patch — no such SSA violation", [
                    'node' => $node
                ]);
                continue;
            }

            // ---- bind_to must be one of valid_bindings ----
            if (!in_array($bindTo, $validBindingsIndex[$node], true)) {
                Log::warning("Rejected patch — illegal SSA binding", [
                    'bind_to' => $bindTo,
                    'allowed' => $validBindingsIndex[$node]
                ]);
                continue;
            }

            // ---- patch is safe ----
            $validated[] = $patch;
        }

        return $validated;
    }

}
