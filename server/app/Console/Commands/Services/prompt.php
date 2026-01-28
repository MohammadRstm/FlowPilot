<?php

namespace App\Console\Commands\Services;

class prompt{
    public static function getAIFallBackDescriptionPrompt(string $class_name, string $path, string $node_type): string {
        return  <<<PROMPT
        You are documenting an n8n automation node.

        Node class: $class_name
        Path: {$path}
        Type: $node_type

        Generate:
        1. A human-friendly display name
        2. A concise one-sentence description of what this node does

        Return JSON:
        {
        "display_name": "...",
        "description": "..."
        }
        PROMPT;
    }
}

