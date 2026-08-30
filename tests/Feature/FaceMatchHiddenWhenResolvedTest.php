<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\FaceMatchResults\FaceMatchResultResource;
use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Suite: Ẩn kết quả "so khớp AI" của hồ sơ đã giải quyết / đã xoá khỏi admin.
 *
 * Mục tiêu: khi hồ sơ trẻ mất tích chuyển sang trạng thái ĐÃ KẾT THÚC
 * (REUNITED / CLOSED / REJECTED / ARCHIVED / UNREACHABLE_FAMILY) hoặc bị xoá,
 * hàng đợi "Đối chiếu khuôn mặt AI" không còn hiện gợi ý PENDING treo, và badge
 * đếm đúng số PENDING còn thao tác được.
 */
class FaceMatchHiddenWhenResolvedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Province $province;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->province = Province::first()
            ?? Province::factory()->create(['id' => 1, 'name' => 'Đà Nẵng']);
    }

    private function makeMissingChild(array $overrides = []): MissingChild
    {
        return MissingChild::create(array_merge([
            'user_id' => $this->user->id,
            'case_code' => 'MC-2026-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'full_name' => 'Nguyễn Văn Test',
            'age_estimate' => 8,
            'gender' => 'MALE',
            'province_id' => $this->province->id,
            'lat' => 16.0544,
            'lng' => 108.2022,
            'missing_date' => now()->subDay(),
            'contact_name' => 'Parent Test',
            'contact_phone' => '+84901234567',
            'status' => 'APPROVED',
        ], $overrides));
    }

    private function makeFoundChild(string $status): FoundChild
    {
        return FoundChild::withoutEvents(fn () => FoundChild::create([
            'user_id' => User::factory()->create()->getKey(),
            'province_id' => $this->province->getKey(),
            'found_date' => now()->format('Y-m-d'),
            'status' => $status,
        ]));
    }

    private function makePendingMatch(MissingChild $missing, FoundChild $found): FaceMatchResult
    {
        return FaceMatchResult::create([
            'missing_id' => $missing->getKey(),
            'found_id' => $found->getKey(),
            'score' => 0.9,
            'status' => 'PENDING',
        ]);
    }

    /** Hồ sơ chuyển REUNITED → PENDING match bị DISMISSED (ẩn khỏi hàng đợi). */
    public function test_reunited_missing_child_dismisses_pending_matches(): void
    {
        $missing = $this->makeMissingChild(['status' => 'APPROVED']);
        $found = $this->makeFoundChild(FoundChild::STATUS_APPROVED);
        $pending = $this->makePendingMatch($missing, $found);

        // Chuyển sang REUNITED qua Eloquent → MissingChildObserver::updated()
        // gọi MissingChildCascadeService::dismissPendingMatches().
        $missing->update(['status' => MissingChild::STATUS_REUNITED]);

        $this->assertSame(
            'DISMISSED',
            $pending->fresh()->status,
            'PENDING match của hồ sơ đã đoàn tụ phải bị ẩn (DISMISSED) khỏi hàng đợi so khớp AI.'
        );
    }

    /** Match CONFIRMED (đã dẫn đến đoàn tù) KHÔNG bị dismiss — giữ lịch sử. */
    public function test_confirmed_match_kept_when_reunited(): void
    {
        $missing = $this->makeMissingChild(['status' => 'APPROVED']);
        $found = $this->makeFoundChild(FoundChild::STATUS_APPROVED);
        $confirmed = FaceMatchResult::create([
            'missing_id' => $missing->getKey(),
            'found_id' => $found->getKey(),
            'score' => 0.95,
            'status' => 'CONFIRMED',
        ]);

        $missing->update(['status' => MissingChild::STATUS_REUNITED]);

        $this->assertSame('CONFIRMED', $confirmed->fresh()->status);
    }

    /** Danh sách admin "Đối chiếu khuôn mặt AI" không hiển thị match của hồ sơ đã đoàn tụ. */
    public function test_admin_list_hides_matches_of_resolved_missing_child(): void
    {
        $missing = $this->makeMissingChild(['status' => MissingChild::STATUS_REUNITED]);
        $found = $this->makeFoundChild(FoundChild::STATUS_APPROVED);
        $this->makePendingMatch($missing, $found);

        // Match của hồ sơ ACTIVE vẫn hiện.
        $active = $this->makeMissingChild(['status' => 'APPROVED']);
        $found2 = $this->makeFoundChild(FoundChild::STATUS_APPROVED);
        $this->makePendingMatch($active, $found2);

        $visible = FaceMatchResultResource::getEloquentQuery()->pluck('id')->all();

        $this->assertNotContains(
            $missing->faceMatchResults()->first()->getKey(),
            $visible,
            'Match của hồ sơ REUNITED phải bị ẩn khỏi danh sách admin.'
        );
        $this->assertContains(
            $active->faceMatchResults()->first()->getKey(),
            $visible,
            'Match của hồ sơ active vẫn phải hiện trong danh sách admin.'
        );
    }
}
