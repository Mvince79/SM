<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LoadTestScriptsTest extends TestCase
{
    public function test_public_script_encodes_the_d05_acceptance_contract(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/scripts/load-test/map-data.js');

        $this->assertStringContainsString("__ENV.TOTAL_VUS || '50'", $script);
        $this->assertStringContainsString("'http_req_failed{phase:load}': ['rate<0.01']", $script);
        $this->assertSame(3, substr_count($script, "'p(95)<1000'"));
        $this->assertStringContainsString('/api/v1/map-data', $script);
        $this->assertStringContainsString('/api/v1/missing-children?q=', $script);
        $this->assertStringContainsString('CONFIRM_TARGET', $script);
    }

    public function test_ai_queue_script_never_contains_a_default_secret(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/scripts/load-test/ai-queue.js');

        $this->assertStringContainsString('__ENV.K6_INTERNAL_KEY', $script);
        $this->assertStringContainsString('/api/internal/face-match/trigger', $script);
        $this->assertStringNotContainsString('change_me', $script);
        $this->assertStringNotContainsString('test-key', $script);
    }
}
