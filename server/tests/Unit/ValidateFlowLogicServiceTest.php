<?php

namespace Tests\Unit;

use App\Service\Copilot\ValidateFlowLogicService;
use App\Service\Copilot\LLMService;
use Tests\TestCase;

class ValidateFlowLogicServiceTest extends TestCase
{
    private ValidateFlowLogicService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ValidateFlowLogicService();
    }
    /**
     * Test that fingerprint is consistent for the same workflow
     */
    public function test_fingerprint_consistency(): void
    {
        $workflow = [
            'nodes' => [
                ['type' => 'Gmail', 'name' => 'email_node'],
                ['type' => 'Slack', 'name' => 'slack_node']
            ],
            'connections' => [
                'email_node' => ['main' => [[['node' => 'slack_node']]]]
            ]
        ];

        // Use reflection to call private method
        $reflection = new \ReflectionClass(ValidateFlowLogicService::class);
        $method = $reflection->getMethod('fingerprintWorkflow');
        $method->setAccessible(true);

        $fingerprint1 = $method->invoke($this->service, $workflow);
        $fingerprint2 = $method->invoke($this->service, $workflow);

        $this->assertEquals($fingerprint1, $fingerprint2);
    }

    /**
     * Test that different workflows produce different fingerprints
     */
    public function test_fingerprint_differs_for_different_workflows(): void
    {
        $workflow1 = [
            'nodes' => [
                ['type' => 'Gmail', 'name' => 'email_node']
            ],
            'connections' => []
        ];

        $workflow2 = [
            'nodes' => [
                ['type' => 'Slack', 'name' => 'slack_node']
            ],
            'connections' => []
        ];

        $reflection = new \ReflectionClass(ValidateFlowLogicService::class);
        $method = $reflection->getMethod('fingerprintWorkflow');
        $method->setAccessible(true);

        $fingerprint1 = $method->invoke($this->service, $workflow1);
        $fingerprint2 = $method->invoke($this->service, $workflow2);

        $this->assertNotEquals($fingerprint1, $fingerprint2);
    }

    /**
     * Test that best workflow is tracked correctly
     */
    public function test_best_workflow_tracking(): void
    {
        $workflow1 = ['nodes' => [['type' => 'Gmail', 'name' => 'node1']], 'connections' => []];
        $workflow2 = ['nodes' => [['type' => 'Slack', 'name' => 'node2']], 'connections' => []];

        $reflection = new \ReflectionClass(ValidateFlowLogicService::class);
        
        // Get the updateBestWorkflow method
        $updateMethod = $reflection->getMethod('updateBestWorkflow');
        $updateMethod->setAccessible(true);

        // First workflow with score 0.7
        $updateMethod->invoke($this->service, $workflow1, 0.7);

        // Second workflow with score 0.85 (should become best)
        $updateMethod->invoke($this->service, $workflow2, 0.85);

        // Verify best workflow property
        $bestWorkflowProperty = $reflection->getProperty('bestWorkflow');
        $bestWorkflowProperty->setAccessible(true);
        $bestWorkflow = $bestWorkflowProperty->getValue($this->service);

        $this->assertEquals($workflow2, $bestWorkflow);
    }
}
