<?php

namespace App\Service\Copilot;

class SSAService
{
    /**
     * Build execution graph from n8n workflow
     */
    public static function buildExecutionGraph(array $workflow): array
    {
        $graph = [];

        // Initialize nodes
        foreach ($workflow['nodes'] as $node) {
            $graph[$node['name']] = [];
        }

        // Fill connections
        foreach ($workflow['connections'] as $startNode => $outputs) {
            foreach ($outputs as $outputName => $connectionArrays) {
                foreach ($connectionArrays as $connArray) {
                    foreach ($connArray as $conn) {
                        $endNode = $conn['node'] ?? null;
                        if ($endNode) {
                            $graph[$startNode][] = $endNode;
                        }
                    }
                }
            }
        }

        return $graph;
    }

    /**
     * Build symbol table from node outputs
     */
    public static function buildSymbolTable(array $workflow): array
    {
        $symbols = [];

        foreach ($workflow['nodes'] as $node) {
            $nodeId = $node['name'];
            $schema = SchemaRegistery::get($node['type']);

            foreach ($schema['outputs'] ?? [] as $field => $type) {
                $symbols[$nodeId][$field] = [
                    'symbol_id' => "{$nodeId}.{$field}",
                    'node_id' => $nodeId,
                    'path' => $field,
                    'type' => $type
                ];
            }
        }

        return $symbols;
    }

    /**
     * Extract all value uses from workflow parameters
     */
    public static function extractValueUses(array $workflow): array
    {
        $uses = [];
        foreach ($workflow['nodes'] as $node) {
            self::scanParams($node['parameters'], $node['name'], $uses);
        }
        return $uses;
    }

    private static function scanParams($value, string $node, array &$uses)
    {
        if (is_string($value)) {
            preg_match_all('/\{\{\s*(.*?)\s*\}\}/', $value, $matches);
            foreach ($matches[1] as $expr) {
                $uses[] = [
                    'using_node' => $node,
                    'expression' => $expr
                ];
            }
        } elseif (is_array($value)) {
            foreach ($value as $v) {
                self::scanParams($v, $node, $uses);
            }
        }
    }

    /**
     * Validate all uses against symbol table and execution graph
     */
    public static function validateUses(array $uses, array $symbols, array $graph): array
    {
        $violations = [];

        foreach ($uses as $use) {
            $candidates = self::resolveCandidates($use, $symbols, $graph);
            $count = count($candidates);

            if ($count === 0) {
                $violations[] = [
                    'type' => 'unresolved',
                    'use' => $use,
                    'candidates' => []
                ];
                continue;
            }

            if ($count > 1) {
                $violations[] = [
                    'type' => 'phi_required',
                    'use' => $use,
                    'candidates' => $candidates
                ];
            }
        }

        return $violations;
    }

    /**
     * Apply patches to workflow parameters
     */
    public static function applyPatches(array $workflow, array $patches): array
    {
        foreach ($patches as $patch) {
            foreach ($workflow['nodes'] as &$node) {
                if ($node['name'] === $patch['node']) {
                    self::rewriteParam($node['parameters'], $patch);
                }
            }
        }
        return $workflow;
    }

    private static function rewriteParam(&$params, array $patch)
    {
        foreach ($params as &$v) {
            if (is_string($v)) {
                $v = str_replace(
                    '{{ $json.' . $patch['field'] . ' }}',
                    '{{ $node["' . explode('.', $patch['bind_to'])[0] . '"].' . explode('.', $patch['bind_to'])[1] . ' }}',
                    $v
                );
            } elseif (is_array($v)) {
                self::rewriteParam($v, $patch);
            }
        }
    }

    /**
     * Check if defNode dominates useNode in the graph (BFS)
     */
    private static function dominates(string $defNode, string $useNode, array $graph): bool
    {
        if ($defNode === $useNode) return true;

        $visited = [];
        $queue = [$defNode];

        while ($queue) {
            $current = array_shift($queue);
            if ($current === $useNode) return true;

            foreach ($graph[$current] ?? [] as $next) {
                if (!isset($visited[$next])) {
                    $visited[$next] = true;
                    $queue[] = $next;
                }
            }
        }

        return false;
    }

    /**
     * Resolve candidate definitions for a use
     */
    private static function resolveCandidates(array $use, array $symbols, array $graph): array
    {
        $expr = $use['expression'];
        $usingNode = $use['using_node'];

        if (!preg_match('/json\.([a-zA-Z0-9_]+)/', $expr, $m)) {
            return [];
        }

        $field = 'json.' . $m[1];
        $candidates = [];

        foreach ($symbols as $defNode => $defs) {
            if (!isset($defs[$field])) continue;
            if (!self::dominates($defNode, $usingNode, $graph)) continue;

            $candidates[] = [
                'symbol_id' => $defs[$field]['symbol_id'],
                'node' => $defNode,
                'path' => $field
            ];
        }

        return $candidates;
    }

    /**
     * Plan φ-nodes for multiple candidate values
     */
    public static function planPhiNodes(array $violations): array
    {
        $phiPlans = [];

        foreach ($violations as $v) {
            if ($v['type'] !== 'phi_required') continue;

            $field = $v['candidates'][0]['path'];
            $usingNode = $v['use']['using_node'];

            $phiPlans[] = [
                'phi_node' => 'Phi_' . str_replace('.', '_', $field) . '_' . uniqid(),
                'field' => $field,
                'sources' => array_map(fn($c) => $c['symbol_id'], $v['candidates']),
                'merge_at' => $usingNode
            ];
        }

        return $phiPlans;
    }

    /**
     * Apply φ-nodes to workflow in n8n-compatible format
     */
    public static function applyPhiNodes(array $workflow, array $phiPlans): array
    {
        foreach ($phiPlans as $phi) {
            $setNode = [
                'name' => $phi['phi_node'],
                'type' => 'n8n-nodes-base.set',
                'typeVersion' => 1,
                'position' => [0, 0],
                'parameters' => [
                    'values' => [
                        'string' => [
                            [
                                'name' => explode('.', $phi['field'])[1],
                                'value' => '{{ $json.' . explode('.', $phi['field'])[1] . ' }}'
                            ]
                        ]
                    ]
                ]
            ];

            $workflow['nodes'][] = $setNode;

            // Ensure connections array exists
            if (!isset($workflow['connections'][$phi['phi_node']])) {
                $workflow['connections'][$phi['phi_node']] = [];
            }
            if (!isset($workflow['connections'][$phi['phi_node']]['main'])) {
                $workflow['connections'][$phi['phi_node']]['main'] = [];
            }

            // Connect sources → phi node
            foreach ($phi['sources'] as $source) {
                [$sourceNode] = explode('.', $source);
                if (!isset($workflow['connections'][$sourceNode])) {
                    $workflow['connections'][$sourceNode] = [];
                }
                if (!isset($workflow['connections'][$sourceNode]['main'])) {
                    $workflow['connections'][$sourceNode]['main'] = [];
                }

                $workflow['connections'][$sourceNode]['main'][] = [
                    [
                        'node' => $phi['phi_node'],
                        'type' => 'main',
                        'index' => 0
                    ]
                ];
            }

            // Connect phi → merge target
            $workflow['connections'][$phi['phi_node']]['main'][] = [
                [
                    'node' => $phi['merge_at'],
                    'type' => 'main',
                    'index' => 0
                ]
            ];
        }

        return $workflow;
    }
}
