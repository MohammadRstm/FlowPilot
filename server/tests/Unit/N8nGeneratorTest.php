<?php

namespace Tests\Unit;

use App\Services\Copilot\Services\WorkflowGeneration;
use PHPUnit\Framework\TestCase;

class N8nGeneratorTest extends TestCase
{
    /**
     * Test buildWorkflowContext with empty flows array
     */
    public function test_buildWorkflowContext_with_empty_flows(): void
    {
        $flows = [];
        
        $result = WorkflowGeneration::buildWorkflowContext($flows);
        
        $this->assertEquals("", $result);
    }

    /**
     * Test buildWorkflowContext with single workflow
     */
    public function test_buildWorkflowContext_with_single_workflow(): void
    {
        $flows = [
            [
                'workflow' => 'Send Email Workflow',
                'nodes_used' => ['Gmail', 'HTTP'],
                'node_count' => 2,
                'raw' => ['id' => 1, 'name' => 'test']
            ]
        ];
        
        $result = WorkflowGeneration::buildWorkflowContext($flows);
        
        $this->assertStringContainsString('Workflow 1', $result);
        $this->assertStringContainsString('Send Email Workflow', $result);
        $this->assertStringContainsString('Gmail', $result);
        $this->assertStringContainsString('HTTP', $result);
        $this->assertStringContainsString('Node Count: 2', $result);
    }

    /**
     * Test buildWorkflowContext with multiple workflows
     */
    public function test_buildWorkflowContext_with_multiple_workflows(): void
    {
        $flows = [
            [
                'workflow' => 'First Workflow',
                'nodes_used' => ['Node1'],
                'node_count' => 1,
                'raw' => ['id' => 1]
            ],
            [
                'workflow' => 'Second Workflow',
                'nodes_used' => ['Node2', 'Node3'],
                'node_count' => 2,
                'raw' => ['id' => 2]
            ]
        ];
        
        $result = WorkflowGeneration::buildWorkflowContext($flows);
        
        $this->assertStringContainsString('Workflow 1', $result);
        $this->assertStringContainsString('Workflow 2', $result);
        $this->assertStringContainsString('First Workflow', $result);
        $this->assertStringContainsString('Second Workflow', $result);
        $this->assertStringContainsString('Node1', $result);
        $this->assertStringContainsString('Node2', $result);
    }

    /**
     * Test buildSchemasContext with empty schemas array
     */
    public function test_buildSchemasContext_with_empty_schemas(): void
    {
        $schemas = [];
        
        $result = WorkflowGeneration::buildSchemasContext($schemas);
        
        $this->assertStringContainsString('You may ONLY use the following n8n node operations', $result);
        $this->assertStringContainsString('Every operation below is valid, ranked, and schema-verified', $result);
        $this->assertStringContainsString('Do NOT invent nodes, resources, operations, or fields', $result);
    }

    /**
     * Test buildSchemasContext with schema operations
     */
    public function test_buildSchemasContext_with_schema_operations(): void
    {
        $schemas = [
            [
                'schema' => [
                    'node' => 'Gmail',
                    'resource' => 'Email',
                    'operation' => 'Send',
                    'display' => 'Send Email',
                    'description' => 'Sends an email via Gmail',
                    'fields' => [
                        ['name' => 'to', 'type' => 'string', 'required' => true],
                        ['name' => 'subject', 'type' => 'string', 'required' => true]
                    ],
                    'inputs' => [
                        ['name' => 'Email Data', 'type' => 'object']
                    ],
                    'outputs' => [
                        ['name' => 'Message ID', 'type' => 'string']
                    ]
                ]
            ]
        ];
        
        $result = WorkflowGeneration::buildSchemasContext($schemas);
        
        $this->assertStringContainsString('NODE: Gmail', $result);
        $this->assertStringContainsString('OPERATION: Email → Send', $result);
        $this->assertStringContainsString('LABEL: Send Email', $result);
        $this->assertStringContainsString('DESCRIPTION: Sends an email via Gmail', $result);
        $this->assertStringContainsString('FIELDS:', $result);
        $this->assertStringContainsString('to (string, required)', $result);
        $this->assertStringContainsString('subject (string, required)', $result);
        $this->assertStringContainsString('INPUTS:', $result);
        $this->assertStringContainsString('Email Data', $result);
        $this->assertStringContainsString('OUTPUTS:', $result);
        $this->assertStringContainsString('Message ID', $result);
    }
}