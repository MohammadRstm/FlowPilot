<?php

namespace App\Services\Copilot\Services;

class WorkflowGeneration{
  
    public static function buildWorkflowContext(array $flows): string{
        if (empty($flows)) {
            return "";
        }

        $sections = [];
        $counter  = 1;

        foreach ($flows as $flow) {
            if (!is_array($flow)) {
                continue;
            }

            $sections[] = self::formatWorkflowSection($flow, $counter++);
        }

        return implode("\n", $sections);
    }

    private static function formatWorkflowSection(array $flow, int $index): string{
        $name     = $flow["workflow"] ?? "Unknown Workflow";
        $nodes    = $flow["nodes_used"] ?? [];
        $count    = $flow["node_count"] ?? count($nodes);
        $raw      = $flow["raw"] ?? $flow;
        $jsonDump = json_encode($raw, JSON_PRETTY_PRINT);

        return implode("\n", [
            "--- Workflow {$index} ---",
            "Name: {$name}",
            "Nodes: " . implode(", ", $nodes),
            "Node Count: {$count}",
            "JSON:",
            $jsonDump,
            ""
        ]);
    }

    public static function buildSchemasContext(array $rankedSchemas): string{
        $grouped = self::groupSchemasByNode($rankedSchemas);
        self::sortOperationsByScore($grouped);

        $lines   = self::schemaIntroText();

        foreach ($grouped as $node => $operations) {
            $lines = array_merge($lines, self::formatNodeSection($node, $operations));
        }

        return implode("\n", $lines);
    }

    private static function groupSchemasByNode(array $rankedSchemas): array{
        $byNode = [];

        foreach ($rankedSchemas as $row) {
            $schema = $row["schema"] ?? [];
            $node   = $schema["node"] ?? "UnknownNode";

            $byNode[$node][] = self::normalizeOperation($schema, $row["score"] ?? 0);
        }

        return $byNode;
    }

    private static function normalizeOperation(array $schema, int $score): array{
        return [
            "resource"    => $schema["resource"] ?? "default",
            "operation"   => $schema["operation"] ?? "default",
            "display"     => $schema["display"] ?? "",
            "description" => $schema["description"] ?? "",
            "fields"      => $schema["fields"] ?? [],
            "inputs"      => $schema["inputs"] ?? [],
            "outputs"     => $schema["outputs"] ?? [],
            "score"       => $score,
        ];
    }

    private static function sortOperationsByScore(array &$grouped): void{
        foreach ($grouped as &$ops) {
            usort($ops, fn ($a, $b) => $b["score"] <=> $a["score"]);
        }
    }

    private static function schemaIntroText(): array{
        return [
            "You may ONLY use the following n8n node operations. If you don't find one here, use one you already know.",
            "Every operation below is valid, ranked, and schema-verified.",
            "Do NOT invent nodes, resources, operations, or fields.",
            ""
        ];
    }

    private static function formatNodeSection(string $node, array $operations): array{
        $lines = [
            "NODE: {$node}",
            str_repeat("=", 50),
        ];

        foreach ($operations as $op) {
            $lines = array_merge($lines, self::formatOperation($op));
        }

        $lines[] = ""; // spacing after each node
        return $lines;
    }

    private static function formatOperation(array $op): array{
        $lines = [
            "OPERATION: {$op["resource"]} → {$op["operation"]}",
        ];

        if ($op["display"]) {
            $lines[] = "LABEL: {$op["display"]}";
        }

        if ($op["description"]) {
            $lines[] = "DESCRIPTION: {$op["description"]}";
        }

        $lines = array_merge($lines, self::formatFields($op["fields"]));
        $lines = array_merge($lines, self::formatInputs($op["inputs"]));
        $lines = array_merge($lines, self::formatOutputs($op["outputs"]));

        $lines[] = ""; // spacing between operations
        return $lines;
    }

    private static function formatFields(array $fields): array{
        if (empty($fields)) {
            return ["FIELDS: none"];
        }

        $lines = ["FIELDS:"];
        foreach ($fields as $f) {
            $required = !empty($f["required"]) ? "required" : "optional";
            $lines[]  = "- {$f["name"]} ({$f["type"]}, {$required})";
        }

        return $lines;
    }

    private static function formatInputs(array $inputs): array{
        if (empty($inputs)) {
            return [];
        }

        $lines = ["INPUTS:"];
        foreach ($inputs as $i) {
            $lines[] = "- {$i["name"]} ({$i["type"]})";
        }

        return $lines;
    }

    private static function formatOutputs(array $outputs): array{
        if (empty($outputs)) {
            return [];
        }

        $lines = ["OUTPUTS:"];
        foreach ($outputs as $o) {
            $lines[] = "- {$o["name"]} ({$o["type"]})";
        }

        return $lines;
    }
}
