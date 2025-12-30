<?php

namespace App\Service\N8N;

use Illuminate\Support\Facades\Http;

class MockDataGenerator{

    public static function fromWorkflow(array $workflow): array{
        $usage = self::extractJsonPaths($workflow);
        $inferred = self::inferVariableTypes($usage);

        $nodes = self::extractNodes($workflow);
        $schemas = self::loadSchemas($nodes);
        $schemaMap = self::buildSchemaFieldMap($schemas);

        return self::buildMockObject($inferred, $schemaMap);
    }


    private static function extractJsonPaths(array $workflow): array{
        $found = [];

        self::walk($workflow, function ($value) use (&$found) {
            if (!is_string($value)) return;

            preg_match_all('/\{\{\s*\$json((?:\[[^\]]+\])+)\\s*\}\}/', $value, $matches);

            foreach ($matches[1] as $rawPath => $value) {
                $found[self::normalizePath($rawPath)][] = $value;
            }
        });

        return array_unique($found);
    }

    private static function walk($data, callable $callback){
        if (is_array($data)) {
            foreach ($data as $v) {
                self::walk($v, $callback);
            }
        } else {
            $callback($data);
        }
    }

    private static function normalizePath(string $raw): string{
        preg_match_all('/\["([^"]+)"\]/', $raw, $m);
        return implode('.', $m[1]);
    }

    private static function buildMockObject(array $inferred, array $schemaMap): array{
        $root = [];

        foreach ($inferred as $path => $type) {
            $key = last(explode('.', $path));

            if (isset($schemaMap[$key])) {
                $type = self::mapSchemaType($schemaMap[$key]);
            }

            self::setTypedValue($root, explode('.', $path), $type);
        }

        return $root;
    }

    private static function mapSchemaType(string $n8nType): string{
        return match ($n8nType) {
            'number' => 'number',
            'boolean' => 'boolean',
            'options' => 'string',
            'string' => 'string',
            'collection' => 'object',
            default => 'string'
        };
    }

    private static function setTypedValue(array &$arr, array $segments, string $type){
        $current = &$arr;

        foreach ($segments as $i => $seg) {
            if ($i === count($segments) - 1) {
                $current[$seg] = self::fakeByType($type, $seg);
            } else {
                if (!isset($current[$seg])) $current[$seg] = [];
                $current = &$current[$seg];
            }
        }
    }

    private static function fakeByType(string $type, string $key){
        return match ($type) {
            'number' => rand(10, 100),
            'boolean' => true,
            'date' => date("Y-m-d H:i:s"),
            default => self::fakeValue($key)
        };
    }

    private static function fakeValue(string $key){
        $k = strtolower($key);

        return match (true) {
            str_contains($k, "email") => "test@example.com",
            str_contains($k, "id") => "12345",
            str_contains($k, "amount"),
            str_contains($k, "price") => 99.99,
            str_contains($k, "event") => "test_event",
            str_contains($k, "name") => "John Doe",
            str_contains($k, "token"),
            str_contains($k, "key") => "abc123",
            default => "test_value"
        };
    }

    private static function inferType(string $expr): string{
        $e = strtolower($expr);

        return match (true) {
            str_contains($e, '*'),
            str_contains($e, '/'),
            str_contains($e, '+'),
            str_contains($e, '-'),
            str_contains($e, '.length') => 'number',

            str_contains($e, '=== true'),
            str_contains($e, '=== false'),
            str_contains($e, '&&'),
            str_contains($e, '||') => 'boolean',

            str_contains($e, 'todate'),
            str_contains($e, 'new date') => 'date',

            str_contains($e, '"'),
            str_contains($e, "'") => 'string',

            default => 'string'
        };
    }

    private static function inferVariableTypes(array $usageMap): array{
        $types = [];

        foreach ($usageMap as $path => $expressions) {
            $counts = [];

            foreach ($expressions as $expr) {
                $t = self::inferType($expr);
                $counts[$t] = ($counts[$t] ?? 0) + 1;
            }

            arsort($counts);
            $types[$path] = array_key_first($counts);
        }

        return $types;
    }

    private static function extractNodes(array $workflow): array{
        return collect($workflow["nodes"] ?? [])
            ->map(fn($n) => explode(".", $n["type"])[2] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private static function loadSchemas(array $nodes): array{
        $endpoint = env("QDRANT_URL")."/collections/n8n_schemas/points/scroll";

        $response = Http::post($endpoint, [
            "limit" => 1000,
            "filter" => [
                "must" => [
                    [
                        "key" => "node",
                        "match" => ["any" => $nodes]
                    ]
                ]
            ]
        ]);

        return collect($response->json("result.points"))
            ->pluck("payload")
            ->all();
    }

    private static function buildSchemaFieldMap(array $schemas): array{
        $map = [];

        foreach ($schemas as $schema) {
            foreach ($schema["fields"] ?? [] as $field) {
                $map[$field["name"]] = $field["type"];
            }
        }

        return $map;
    }

}
