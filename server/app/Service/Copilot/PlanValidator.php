<?php

namespace App\Service\Copilot;

class PlanValidator{

   public static function validate(array $plan, array $allowedNodes): array {
        $errors = [];
        $nodes  = $plan["nodes"] ?? [];

        if (count($nodes) < 2) {
            $errors[] = "Plan must contain at least 2 nodes";
        }

        // build lookup
        $nodeNames = [];
        foreach ($nodes as $n) {
            $name = $n["name"] ?? null;
            if (!$name) {
                $errors[] = "Node missing name";
                continue;
            }

            $nodeNames[$name] = true;

            // illegal node
            if (!in_array(strtolower($name), $allowedNodes)) {
                $errors[] = "Illegal node: $name";
            }
        }

        // check triggers
        $triggers = array_filter($nodes, fn($n) => $n["role"] === "trigger");
        if (count($triggers) !== 1) {
            $errors[] = "Plan must have exactly 1 trigger";
        }

        // check connections
        foreach ($nodes as $n) {
            if ($n["role"] === "trigger") continue;

            $from = $n["from"] ?? null;
            if (!$from) {
                $errors[] = "Node {$n["name"]} has no 'from'";
                continue;
            }

            // If.true, If.false
            if (str_contains($from, ".")) {
                [$base, $branch] = explode(".", $from, 2);

                if (!isset($nodeNames[$base])) {
                    $errors[] = "Invalid branch source $from";
                }

                if ($branch !== "true" && $branch !== "false") {
                    $errors[] = "Invalid branch $from";
                }
            } else {
                if (!isset($nodeNames[$from])) {
                    $errors[] = "Invalid 'from' reference: $from";
                }
            }
        }

        // cycle detection
        if (self::hasCycle($nodes)) {
            $errors[] = "Plan contains a cycle";
        }

        return [
            "ok" => empty($errors),
            "errors" => $errors
        ];
    }

    private static function hasCycle(array $nodes): bool {
        $graph = [];

        foreach ($nodes as $n) {
            $from = $n["from"];
            if (!$from) continue;

            $src = explode(".", $from)[0];
            $graph[$src][] = $n["name"];
        }

        $visited = [];
        $stack   = [];

        foreach ($graph as $n => $_) {
            if (self::dfs($n, $graph, $visited, $stack)) {
                return true;
            }
        }

        return false;
    }

    private static function dfs($node, &$graph, &$visited, &$stack): bool {
        if (!empty($stack[$node])) return true;
        if (!empty($visited[$node])) return false;

        $visited[$node] = true;
        $stack[$node] = true;

        foreach ($graph[$node] ?? [] as $next) {
            if (self::dfs($next, $graph, $visited, $stack)) {
                return true;
            }
        }

        unset($stack[$node]);
        return false;
    }



    
}
