<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AiServiceOperationsConfigTest extends TestCase
{
    public function test_ai_service_logrotate_policy_is_daily_and_keeps_seven_compressed_archives(): void
    {
        $config = file_get_contents(dirname(__DIR__, 2).'/docs/logrotate-timtre-ai.example');

        $this->assertNotFalse($config);
        $this->assertStringContainsString('/var/log/timtre-ai/service.log', $config);
        $this->assertStringContainsString('daily', $config);
        $this->assertStringContainsString('rotate 7', $config);
        $this->assertStringContainsString('compress', $config);
        $this->assertStringContainsString('copytruncate', $config);
    }

    public function test_supervisor_delegates_rotation_and_configures_restart_alert(): void
    {
        $root = dirname(__DIR__, 2);
        $supervisor = file_get_contents($root.'/docs/supervisor-timtre-ai.conf.example');
        $listener = file_get_contents($root.'/docs/supervisor-timtre-ai-alert.conf.example');
        $script = file_get_contents($root.'/scripts/monitoring/timtre-ai-restart-alert');

        $this->assertNotFalse($supervisor);
        $this->assertStringContainsString('autorestart=true', $supervisor);
        $this->assertStringContainsString('startretries=3', $supervisor);
        $this->assertStringContainsString('stdout_logfile_maxbytes=0', $supervisor);
        $this->assertStringContainsString('events=PROCESS_STATE_EXITED', (string) $listener);
        $this->assertStringContainsString('AI_RESTART_ALERT_WINDOW_SECONDS="300"', (string) $listener);
        $this->assertStringContainsString('AI_RESTART_ALERT_THRESHOLD="3"', (string) $listener);
        $this->assertStringContainsString("expected:0", (string) $script);
        $this->assertStringContainsString('AI_ALERT_WEBHOOK_URL', (string) $script);
    }
}
