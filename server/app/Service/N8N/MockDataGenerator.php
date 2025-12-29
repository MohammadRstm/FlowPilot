<?php

namespace App\Service\N8N;

class MockDataGenerator{

    public static function fromWorkflow(array $workflow): array{
        $paths = self::extractJsonPaths($workflow);
        return self::buildMockObject($paths);
    }


    private static function extractJsonPaths(array $workflow): array{
        $found = [];

        self::walk($workflow, function ($value) use (&$found) {
            if (!is_string($value)) return;

            preg_match_all('/\{\{\s*\$json((?:\[[^\]]+\])+)\\s*\}\}/', $value, $matches);

            foreach ($matches[1] as $rawPath) {
                $found[] = self::normalizePath($rawPath);
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

    private static function buildMockObject(array $paths): array{
        $root = [];

        foreach ($paths as $path) {
            $segments = explode('.', $path);
            self::setNestedValue($root, $segments);
        }

        return $root ?: ["test" => true];
    }

    private static function setNestedValue(array &$arr, array $segments){
        $current = &$arr;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $current[$segment] = self::fakeValue($segment);
            } else {
                if (!isset($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }
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







}
