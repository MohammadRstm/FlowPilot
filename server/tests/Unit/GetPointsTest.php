<?php

namespace Tests\Unit;

use App\Service\Copilot\GetPoints;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GetPointsTest extends TestCase
{
    /**
     * Test parseNodeVersion correctly parses versioned class names
     */
    public function test_parse_node_version_with_version(): void
    {
        $reflection = new \ReflectionClass(GetPoints::class);
        $method = $reflection->getMethod('parseNodeVersion');
        $method->setAccessible(true);

        $result = $method->invoke(null, "GmailV2");
        
        $this->assertEquals("Gmail", $result['base']);
        $this->assertEquals(2, $result['version']);
    }

    /**
     * Test parseNodeVersion handles unversioned class names
     */
    public function test_parse_node_version_without_version(): void
    {
        $reflection = new \ReflectionClass(GetPoints::class);
        $method = $reflection->getMethod('parseNodeVersion');
        $method->setAccessible(true);

        $result = $method->invoke(null, "SlackLegacy");
        
        $this->assertEquals("SlackLegacy", $result['base']);
        $this->assertEquals(0, $result['version']);
    }

    /**
     * Test keepLatestVersions keeps only the highest version of each node
     */
    public function test_keep_latest_versions_filters_old_versions(): void
    {
        $reflection = new \ReflectionClass(GetPoints::class);
        $method = $reflection->getMethod('keepLatestVersions');
        $method->setAccessible(true);

        $hits = [
            [
                'payload' => [
                    'class_name' => 'GmailV1',
                    'node_id' => 'gmail-v1'
                ]
            ],
            [
                'payload' => [
                    'class_name' => 'GmailV3',
                    'node_id' => 'gmail-v3'
                ]
            ],
            [
                'payload' => [
                    'class_name' => 'GmailV2',
                    'node_id' => 'gmail-v2'
                ]
            ],
        ];

        $result = $method->invoke(null, $hits);
        
        // Should only keep GmailV3
        $this->assertCount(1, $result);
        $this->assertEquals("GmailV3", $result[0]['payload']['class_name']);
    }

    /**
     * Test filterByAdaptiveScore returns top scorer when best >= 0.6
     */
    public function test_filter_by_adaptive_score_high_confidence(): void
    {
        // Mock HTTP facade to prevent actual calls
        Http::fake();

        $reflection = new \ReflectionClass(GetPoints::class);
        $method = $reflection->getMethod('filterByAdaptiveScore');
        $method->setAccessible(true);

        $hits = [
            ['score' => 0.85],
            ['score' => 0.65],
            ['score' => 0.55],
            ['score' => 0.45],
        ];

        $result = $method->invoke(null, $hits, 8);
        
        // With ratio 0.35 for 0.85, threshold = 0.85 * 0.35 = 0.2975
        // Should keep all >= 0.2975
        $this->assertGreaterThan(1, count($result));
        $this->assertEquals(0.85, $result[0]['score']);
    }

    /**
     * Test filterByAdaptiveScore returns only top 1 when best < 0.25
     */
    public function test_filter_by_adaptive_score_low_confidence(): void
    {
        // Mock HTTP facade to prevent actual calls
        Http::fake();

        $reflection = new \ReflectionClass(GetPoints::class);
        $method = $reflection->getMethod('filterByAdaptiveScore');
        $method->setAccessible(true);

        $hits = [
            ['score' => 0.20],
            ['score' => 0.15],
            ['score' => 0.10],
        ];

        $result = $method->invoke(null, $hits, 8);
        
        // Should only keep top 1 when best < 0.25
        $this->assertCount(1, $result);
        $this->assertEquals(0.20, $result[0]['score']);
    }
}
