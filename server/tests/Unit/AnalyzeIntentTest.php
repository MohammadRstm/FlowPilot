<?php

namespace Tests\Unit;

use App\Services\Copilot\Services\AnalyzeIntent;
use Tests\TestCase;

class AnalyzeIntentTest extends TestCase
{
    /**
     * Test that normalizeNode removes special characters and converts to lowercase
     */
    public function test_normalize_node_removes_special_characters(): void
    {
        $input = "Gmail-Node_v2.5";
        $result = AnalyzeIntent::normalizeNode($input);
        
        $this->assertEquals("gmailnodev25", $result);
    }

    /**
     * Test that normalizeNodes removes duplicates and normalizes multiple nodes
     */
    public function test_normalize_nodes_removes_duplicates(): void
    {
        $input = [
            "Gmail Node",
            "GMAIL NODE",
            "Gmail-Node",
            "Slack API",
            "slack-api"
        ];
        
        $result = AnalyzeIntent::normalizeNodes($input);
        
        // Should only have 2 unique entries: gmailnode and slackapi
        $this->assertCount(2, $result);
        $this->assertContains("gmailnode", $result);
        $this->assertContains("slackapi", $result);
    }

    /**
     * Test that buildWorkflowEmbeddingQuery constructs proper query string
     */
    public function test_build_workflow_embedding_query_with_all_fields(): void
    {
        $analysis = [
            "intent" => "Send emails to contacts",
            "trigger" => "new-record",
            "nodes" => ["gmail", "database"]
        ];
        
        $question = "How do I send emails when a new record is added?";
        $result = AnalyzeIntent::buildWorkflowEmbeddingQuery($analysis, $question);
        
        $this->assertStringContainsString("Send emails to contacts", $result);
        $this->assertStringContainsString("Triggered by new-record", $result);
        $this->assertStringContainsString("gmail", $result);
        $this->assertStringContainsString("database", $result);
        $this->assertStringContainsString($question, $result);
    }

    /**
     * Test that buildWorkflowEmbeddingQuery handles missing optional fields
     */
    public function test_build_workflow_embedding_query_with_minimal_fields(): void
    {
        $analysis = [
            "intent" => "Process data",
            "trigger" => "",
            "nodes" => []
        ];
        
        $question = "Process incoming data";
        $result = AnalyzeIntent::buildWorkflowEmbeddingQuery($analysis, $question);
        
        $this->assertStringContainsString("Process data", $result);
        $this->assertStringContainsString($question, $result);
        $this->assertStringNotContainsString("Triggered by", $result);
    }
}
