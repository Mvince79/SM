<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Settings\SettingRegistry;
use App\Settings\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Đảm bảo hệ thống cài đặt: override config đúng, clamp giá trị, validate chéo,
 * reset và ghi audit log — mà KHÔNG phá logic đọc config() cũ.
 */
class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset sạch để mỗi test độc lập (xoá cache + xoá bản ghi DB + về default).
        Cache::forget('system_settings_effective');
        app(SettingService::class)->resetAll();
    }

    public function test_defaults_match_config_files(): void
    {
        $service = app(SettingService::class);

        $this->assertSame(0.70, $service->get('score_threshold'));
        $this->assertSame(0.45, $service->get('search_threshold'));
        $this->assertSame([5, 10], $service->get('reminder_milestones'));
        $this->assertSame('days', $service->get('reminder_time_unit'));
        $this->assertSame(15, $service->get('escalate_after_days'));
        $this->assertSame(20, $service->get('close_after_days'));

        // Khi chưa có bản ghi, config gốc không đổi.
        $this->assertSame(0.70, config('face_match.score_threshold'));
        $this->assertSame([5, 10], config('notification.reminder_milestones'));
    }

    public function test_set_many_overrides_config_at_runtime(): void
    {
        app(SettingService::class)->setMany([
            'score_threshold' => 0.82,
            'reminder_time_unit' => 'minutes',
            'reminder_milestones' => [3, 7],
            'escalate_after_days' => 12,
            'close_after_days' => 18,
        ]);

        // Config bị ghi đè → các chỗ đọc cũ tự nhận giá trị mới.
        $this->assertSame(0.82, config('face_match.score_threshold'));
        $this->assertSame('minutes', config('notification.reminder_time_unit'));
        $this->assertSame([3, 7], config('notification.reminder_milestones'));
        $this->assertSame(12, config('notification.escalate_after_days'));
        $this->assertSame(18, config('notification.close_after_days'));

        // Service cũng trả giá trị mới.
        $this->assertSame(0.82, app(SettingService::class)->get('score_threshold'));
    }

    public function test_values_are_clamped_to_bounds(): void
    {
        app(SettingService::class)->setMany([
            'timeout' => 9999,        // max 60
            'search_threshold' => -5, // min 0.20
            'sync_retry_attempts' => -3, // min 0
        ]);

        $this->assertSame(60, app(SettingService::class)->get('timeout'));
        $this->assertSame(0.20, app(SettingService::class)->get('search_threshold'));
        $this->assertSame(0, app(SettingService::class)->get('sync_retry_attempts'));
    }

    public function test_milestones_normalized_to_unique_sorted_ints(): void
    {
        app(SettingService::class)->setMany([
            'reminder_milestones' => [3, 1, 3, 2],
            'escalate_after_days' => 30,
            'close_after_days' => 40,
        ]);

        $this->assertSame([1, 2, 3], app(SettingService::class)->get('reminder_milestones'));
    }

    public function test_invalid_select_falls_back_to_default(): void
    {
        app(SettingService::class)->setMany([
            'reminder_time_unit' => 'not-a-real-unit',
        ]);

        $this->assertSame('days', app(SettingService::class)->get('reminder_time_unit'));
    }

    public function test_cross_field_validation_blocks_invalid_combo(): void
    {
        $this->expectException(ValidationException::class);

        app(SettingService::class)->setMany([
            'escalate_after_days' => 50,
            'close_after_days' => 20, // phải > escalate
        ]);
    }

    public function test_high_threshold_must_exceed_match_threshold(): void
    {
        $this->expectException(ValidationException::class);

        app(SettingService::class)->setMany([
            'score_threshold' => 0.90,
            'high_confidence_threshold' => 0.80,
        ]);
    }

    public function test_audit_log_recorded_on_change(): void
    {
        app(SettingService::class)->setMany(['score_threshold' => 0.75]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'system_settings.updated',
            'target_type' => 'system_settings',
        ]);

        $log = AuditLog::where('action', 'system_settings.updated')->latest()->first();
        $this->assertSame(0.75, $log->new_value['score_threshold']);
    }

    public function test_reset_returns_to_defaults(): void
    {
        // 0.75 là combo hợp lệ (search 0.45 < 0.75 < high 0.85).
        app(SettingService::class)->setMany(['score_threshold' => 0.75]);
        $this->assertSame(0.75, app(SettingService::class)->get('score_threshold'));

        app(SettingService::class)->resetAll();

        $this->assertSame(0.70, config('face_match.score_threshold'));
        $this->assertSame(0.70, app(SettingService::class)->get('score_threshold'));
        $this->assertDatabaseCount('system_settings', 0);
    }

    public function test_registry_defaults_equal_config_files(): void
    {
        // Rào chắn an toàn: nếu ai sửa config mặc định mà quên sửa registry,
        // behaviour sẽ lệch. Đảm bảo 2 nguồn khớp nhau.
        $this->assertSame(
            config('face_match.score_threshold'),
            SettingRegistry::defaultValue('face_match', 'score_threshold'),
        );
        $this->assertSame(
            config('notification.escalate_after_days'),
            SettingRegistry::defaultValue('notification', 'escalate_after_days'),
        );
    }

    public function test_photo_search_rate_limit_defaults_and_override(): void
    {
        $service = app(SettingService::class);

        // Mặc định 5 lần / 60 phút (khớp với route throttle cũ 5,60).
        $this->assertSame(5, $service->get('photo_search_rate_limit'));
        $this->assertSame(60, $service->get('photo_search_rate_window'));

        $service->setMany([
            'photo_search_rate_limit' => 20,
            'photo_search_rate_window' => 30,
        ]);

        $this->assertSame(20, $service->get('photo_search_rate_limit'));
        $this->assertSame(30, $service->get('photo_search_rate_window'));

        // Clamp về giới hạn an toàn.
        $service->setMany(['photo_search_rate_limit' => 9999]);
        $this->assertSame(100, $service->get('photo_search_rate_limit'));
    }
}
