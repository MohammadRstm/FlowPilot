<?php

namespace App\Service\Copilot;

class SchemaRegistery{
    /**
     * Example internal storage:
     * [
     *   'n8n-nodes-base.googleSheets' => [
     *       'outputs' => [
     *           'json.email' => 'string',
     *           'json.id' => 'number'
     *       ]
     *   ]
     * ]
     */
    private static array $schemas = [];

    public static function load(array $schemas): void
    {
        self::$schemas = [];

        foreach ($schemas as $schema) {
            self::$schemas[$schema['node']] = [
                'outputs' => self::flattenOutputs($schema['fields'] ?? [])
            ];
        }
    }

    public static function get(string $nodeType): array
    {
        return self::$schemas[$nodeType] ?? ['outputs' => []];
    }

    private static function flattenOutputs(array $fields): array
    {
        $out = [];

        foreach ($fields as $field) {
            if (!empty($field['name']) && !empty($field['type'])) {
                $out['json.' . $field['name']] = $field['type'];
            }
        }

        return $out;
    }
}
