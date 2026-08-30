<?php

namespace Tests\Feature;

use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use App\Services\MissingChildCascadeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Giai đoạn 4 — Xác minh (1) bộ lọc "Đã kết thúc" gom CLOSED+REJECTED và
 * (2) phát hiện/kết luận "đã hoàn tác" (revert via cascade) trên lịch sử đối chiếu.
 *
 * Không phụ thuộc Livewire/Filament render để giữ chạy ổn định; logic filter được
 * phủ qua scopeEnded(), logic hoàn tác qua FaceMatchResult::isRevertedViaCascade().
 */
class FoundChildClosedAndRevertTest extends TestCase
{
    use RefreshDatabase;

    private function makeFoundChild(string $status): FoundChild
    {
        $province = Province::query()->first() ?? Province::factory()->create();

        return FoundChild::withoutEvents(fn () => FoundChild::create([
            'user_id' => User::factory()->create()->getKey(),
            'province_id' => $province->getKey(),
            'found_date' => now()->format('Y-m-d'),
            'status' => $status,
        ]));
    }

    public function test_ended_scope_returns_only_closed_and_rejected(): void
    {
        $closed = $this->makeFoundChild(FoundChild::STATUS_CLOSED);
        $rejected = $this->makeFoundChild(FoundChild::STATUS_REJECTED);
        $this->makeFoundChild(FoundChild::STATUS_PENDING);
        $this->makeFoundChild(FoundChild::STATUS_APPROVED);

        $ended = FoundChild::query()->ended()->get();

        $this->assertTrue($ended->contains($closed));
        $this->assertTrue($ended->contains($rejected));
        $this->assertCount(2, $ended, 'Chỉ CLOSED + REJECTED mới thuộc nhóm "đã kết thúc".');
    }

    public function test_cascade_dismissed_result_is_detected_as_reverted(): void
    {
        $found = $this->makeFoundChild(FoundChild::STATUS_MATCHED);
        $missing = MissingChild::withoutEvents(fn () => \Database\Factories\MissingChildFactory::new()->create([
            'status' => MissingChild::STATUS_FOUND,
            'found_at' => now(),
        ]));

        // prev status KHÔNG nằm trong $fillable → gán qua property + saveQuietly.
        $result = FaceMatchResult::create([
            'missing_id' => $missing->getKey(),
            'found_id' => $found->getKey(),
            'status' => 'CONFIRMED',
            'score' => 0.9,
            'confidence_level' => 'HIGH',
            'source' => 'MANUAL',
            'is_matched' => true,
        ]);
        $result->found_child_prev_status = FoundChild::STATUS_APPROVED;
        $result->missing_child_prev_status = MissingChild::STATUS_APPROVED;
        $result->saveQuietly();

        // Trạng thái CONFIRMED → chưa phải hoàn tác.
        $this->assertFalse($result->isRevertedViaCascade());
        $this->assertNull($result->revert_summary);

        // Mô phỏng xoá MissingChild -> cascade bác bỏ kết quả + lưu prev status.
        app(MissingChildCascadeService::class)->handleRemoval($missing, includePending: true);

        $result->refresh();
        $this->assertSame('DISMISSED', $result->status);
        $this->assertTrue($result->isRevertedViaCascade());
        $this->assertStringContainsString('Trẻ tìm thấy ← Đã duyệt', (string) $result->revert_summary);
        $this->assertStringContainsString('Trẻ mất tích ← Đã duyệt', (string) $result->revert_summary);
    }

    public function test_manual_dismissed_result_is_not_revert(): void
    {
        $found = $this->makeFoundChild(FoundChild::STATUS_MATCHED);
        $missing = MissingChild::withoutEvents(fn () => \Database\Factories\MissingChildFactory::new()->create([
            'status' => MissingChild::STATUS_FOUND,
            'found_at' => now(),
        ]));

        $result = FaceMatchResult::create([
            'missing_id' => $missing->getKey(),
            'found_id' => $found->getKey(),
            'status' => 'DISMISSED',
            'score' => 0.5,
            'confidence_level' => 'LOW',
            'source' => 'MANUAL',
            'is_matched' => false,
        ]);

        $this->assertFalse($result->isRevertedViaCascade(), 'Bác bỏ thủ công không có prev status → không phải hoàn tác.');
        $this->assertNull($result->revert_summary);
    }
}
