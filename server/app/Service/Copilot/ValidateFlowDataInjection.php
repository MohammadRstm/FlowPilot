<?php

namespace App\Service\Copilot;

use Illuminate\Support\Facades\Log;

class ValidateFlowDataInjection{
    private int $maxRetries = 3;
    private float $scoreThreshold = 0.9;

    private float $bestScore = 0.0;
    private ?array $bestWorkflow = null;

    public function execute(array $workflow, string $question, array $totalPoints): array{
        Log::info("DATA FLOW VALIDATION START");

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            Log::info("VALIDATION PASS #{$attempt}");

            $result = LLMService::validateDataFlow($workflow, $question, $totalPoints);

            $score = (float)($result['score'] ?? 0);
            $errors = $result['errors'] ?? [];

            Log::info("Validation score", [
                'attempt' => $attempt,
                'score' => $score,
                'errors' => count($errors),
                'errors_content' => $errors
            ]);

            if ($score > $this->bestScore) {
                $this->bestScore = $score;
                $this->bestWorkflow = $workflow;

                Log::info("New best workflow stored", [
                    'score' => $score
                ]);
            }

            if ($score >= $this->scoreThreshold) {
                Log::info("Workflow accepted — threshold reached");
                return $workflow;
            }

            if (empty($errors)) {
                Log::warning("No explicit errors but score below threshold — stopping");
                break;
            }

            Log::info("Attempting repair", [
                'attempt' => $attempt,
                'error_count' => count($errors)
            ]);

            $repaired = LLMService::repairWorkflowDataFlow(
                json_encode($workflow, JSON_UNESCAPED_SLASHES),
                $errors,
                $totalPoints
            );

            $decodedrepair = json_decode($repaired , true);

            if (!is_array($decodedrepair)) {
                Log::error("Repair returned invalid JSON, aborting");
                break;
            }

            $workflow = $decodedrepair;
        }

        Log::info("Validation loop finished", [
            'bestScore' => $this->bestScore
        ]);

        return $this->bestWorkflow ?? $workflow;
    }
}
