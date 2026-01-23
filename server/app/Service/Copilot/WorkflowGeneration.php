<?php

namespace App\Service\Copilot;

use function PHPUnit\Framework\isArray;

class WorkflowGeneration{

    public static function buildWorkflowContext(array $flows): string{
        if(empty($flows)){
            return "";
        }

        $out = "";
        $counter = 1;


        foreach ($flows as $flow){ 
            // if it's not an array now, skip it
            if (!is_array($flow)) {
                continue;
            }

            $name  = $flow["workflow"] ?? "Unknown Workflow";
            $nodes = $flow["nodes_used"] ?? [];
            $count = $flow["node_count"] ?? count($nodes);
            $raw   = $flow["raw"] ?? $flow;

            $out .= "\n--- Workflow {$counter} ---\n";
            $out .= "Name: {$name}\n";
            $out .= "Nodes: " . implode(", ", $nodes) . "\n";
            $out .= "Node Count: {$count}\n";
            $out .= "JSON:\n" . json_encode($raw, JSON_PRETTY_PRINT) . "\n";

            $counter++;
        }

        return $out;
    }

    public static function buildSchemasContext(array $rankedSchemas): string{
        $byNode = [];

        foreach ($rankedSchemas as $row) {
            $s = $row["schema"];
            $node = $s["node"];

            $byNode[$node][] = [
                "resource"    => $s["resource"] ?? "default",
                "operation"   => $s["operation"] ?? "default",
                "display"     => $s["display"] ?? "",
                "description" => $s["description"] ?? "",
                "fields"      => $s["fields"] ?? [],
                "inputs"      => $s["inputs"] ?? [],
                "outputs"     => $s["outputs"] ?? [],
            ];
        }

        foreach ($byNode as &$ops) {
            usort($ops, fn($a, $b) => $b["score"] <=> $a["score"]);
        }

        $out = [];
        $out[] = "You may ONLY use the following n8n node operations.";
        $out[] = "Every operation below is valid, ranked, and schema-verified.";
        $out[] = "Do NOT invent nodes, resources, operations, or fields.";
        $out[] = "";

        foreach ($byNode as $node => $operations) {
            $out[] = "NODE: {$node}";
            $out[] = str_repeat("=", 50);

            foreach ($operations as $op) {
                $out[] = "OPERATION: {$op["resource"]} → {$op["operation"]}";
                if ($op["display"]) {
                    $out[] = "LABEL: {$op["display"]}";
                }
                if ($op["description"]) {
                    $out[] = "DESCRIPTION: {$op["description"]}";
                }

                // Fields
                if (!empty($op["fields"])) {
                    $out[] = "FIELDS:";
                    foreach ($op["fields"] as $f) {
                        $req = !empty($f["required"]) ? "required" : "optional";
                        $out[] = "- {$f["name"]} ({$f["type"]}, {$req})";
                    }
                } else {
                    $out[] = "FIELDS: none";
                }

                // Inputs
                if (!empty($op["inputs"])) {
                    $out[] = "INPUTS:";
                    foreach ($op["inputs"] as $i) {
                        $out[] = "- {$i["name"]} ({$i["type"]})";
                    }
                }

                // Outputs
                if (!empty($op["outputs"])) {
                    $out[] = "OUTPUTS:";
                    foreach ($op["outputs"] as $o) {
                        $out[] = "- {$o["name"]} ({$o["type"]})";
                    }
                }

                $out[] = ""; // spacing between operations
            }

            $out[] = ""; // spacing between nodes
        }

        return implode("\n", $out);
    }



}


