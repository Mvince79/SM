<?php

namespace Tests\Feature;

use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * S-03 — Giao diện kết quả dạng thẻ cuộn.
 * S-04 — Cảnh báo độ tin cậy & giới hạn sử dụng.
 */
class PhotoSearchResultsTest extends TestCase
{
    use RefreshDatabase;

    private function pageContent(): string
    {
        $response = $this->get(route('photo-search.index'));
        $response->assertOk();

        return $response->getContent();
    }

    /**
     * Hồi quy: `results.blade.php` từng khai báo `@extends` + `@section('content')`
     * dù được `@include` từ index → section 'content' của index bị ghi đè và TOÀN
     * BỘ form upload biến mất khỏi trang (trang chỉ còn khối kết quả đang ẩn).
     */
    public function test_page_renders_upload_form_and_result_block_exactly_once(): void
    {
        $html = $this->pageContent();

        foreach (['id="photo-search-form"', 'id="photo-input"', 'id="drop-zone"', 'id="search-btn"'] as $needle) {
            $this->assertSame(
                1,
                substr_count($html, $needle),
                "Form upload phải xuất hiện đúng 1 lần trên trang ({$needle}).",
            );
        }

        foreach (['id="loading-state"', 'id="results-area"', 'id="results-carousel"', 'id="no-results"'] as $needle) {
            $this->assertSame(
                1,
                substr_count($html, $needle),
                "Khối kết quả phải xuất hiện đúng 1 lần — trùng ID sẽ làm getElementById lấy sai node ({$needle}).",
            );
        }

        $this->assertSame(1, substr_count($html, '<!DOCTYPE'), 'Layout không được render lồng 2 lần.');
    }

    /** DoD S-03 #1 — carousel dùng scroll-snap, bề rộng thẻ theo % track (không phải vw). */
    public function test_carousel_uses_scroll_snap_utility_and_not_viewport_widths(): void
    {
        $html = $this->pageContent();

        $this->assertStringContainsString('result-carousel', $html);
        $this->assertStringNotContainsString(
            'w-[85vw]',
            $html,
            'Bề rộng theo vw bỏ qua padding của container → tràn/vỡ layout ở 375px.',
        );

        $css = file_get_contents(resource_path('css/app.css'));

        foreach ([
            'scroll-snap-type: x mandatory',
            'scroll-snap-align: start',
            'scroll-snap-stop: always',
            'overscroll-behavior-x: contain',
        ] as $rule) {
            $this->assertStringContainsString($rule, $css, "app.css phải có `{$rule}` cho carousel kết quả.");
        }
    }

    /** DoD S-03 #4 — loading dùng .skeleton-shimmer + dòng chữ động, KHÔNG dùng .ai-scanner-overlay. */
    public function test_loading_state_uses_skeleton_shimmer_and_animated_text(): void
    {
        $html = $this->pageContent();

        $this->assertStringContainsString('skeleton-shimmer', $html);
        $this->assertStringContainsString('scanning-text', $html);
        $this->assertStringNotContainsString(
            'ai-scanner-overlay',
            $html,
            '.ai-scanner-overlay đã bị loại bỏ, không được dùng lại.',
        );

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringNotContainsString(
            '.ai-scanner-overlay',
            $css,
            '.ai-scanner-overlay phải được xoá khỏi app.css.',
        );
        $this->assertStringContainsString('@keyframes scanning-dots', $css);
    }

    /** DoD S-03 #5 — chỉ 3 token màu: Carmine (primary) / Navy (brand) / Forest (success). */
    public function test_only_three_color_tokens_are_used(): void
    {
        $html = $this->pageContent();

        // Lấy riêng phần khối tìm kiếm bằng ảnh (bỏ nav/footer dùng chung).
        $start = strpos($html, 'id="photo-search-form"');
        $end = strpos($html, 'id="no-results"');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $block = substr($html, $start, $end - $start);

        foreach (['amber', 'rose-', 'emerald', 'bg-navy', 'text-red-', 'blue-'] as $offToken) {
            $this->assertStringNotContainsString(
                $offToken,
                $block,
                "Khối S-03 chỉ được dùng token primary/brand/success — phát hiện `{$offToken}`.",
            );
        }
    }

    /** DoD S-04 — dòng cảnh báo luôn hiển thị, không bị JS ẩn, không khẳng định chắc chắn. */
    public function test_ai_disclaimer_is_always_visible_and_never_asserts_certainty(): void
    {
        $html = $this->pageContent();
        $disclaimer = 'Kết quả do AI gợi ý, có thể không chính xác 100%';

        $this->assertStringContainsString($disclaimer, $html);
        $this->assertStringContainsString('vui lòng xác minh qua kênh chính thức trước khi hành động', $html);

        // Không còn `id="warning-box"` để JS bật/tắt → không thể ẩn.
        $this->assertStringNotContainsString(
            'id="warning-box"',
            $html,
            'Dòng cảnh báo không được có handle để JS ẩn/hiện.',
        );
        $this->assertStringNotContainsString('warningBox', $html);

        // Thẻ <div> bọc gần nhất của dòng cảnh báo không được mang class `hidden`.
        // Chỉ soi danh sách class của đúng thẻ mở đó (tránh khớp nhầm `aria-hidden`).
        $pos = strpos($html, $disclaimer);
        $openTagPos = strrpos(substr($html, 0, $pos), '<div');
        $this->assertNotFalse($openTagPos);

        $openTag = substr($html, $openTagPos, strpos($html, '>', $openTagPos) - $openTagPos + 1);
        preg_match('/class="([^"]*)"/', $openTag, $m);
        $classes = preg_split('/\s+/', trim($m[1] ?? ''));

        $this->assertNotContains(
            'hidden',
            $classes,
            'Thẻ bọc dòng cảnh báo không được có class `hidden`.',
        );

        // Không dùng ngôn từ khẳng định chắc chắn.
        foreach (['đây chính là bé', 'Đây đúng là bé', 'chắc chắn là bé'] as $phrase) {
            $this->assertStringNotContainsString($phrase, $html, "Ngôn từ khẳng định bị cấm: `{$phrase}`.");
        }
    }

    /** DoD S-03 #2/#3 — nút báo tin trung tính + luôn có đường dẫn found-children.create. */
    public function test_report_buttons_link_to_found_children_create(): void
    {
        $html = $this->pageContent();

        $this->assertStringContainsString('Báo tin ngay', $html);
        $this->assertStringContainsString(route('found-children.create') . '?match=', $html);

        // Không có kết quả → vẫn có thẻ hướng dẫn + CTA, không bao giờ trống.
        $this->assertStringContainsString('Chưa tìm thấy hồ sơ tương đồng', $html);
        $this->assertStringContainsString('Báo tin thấy trẻ', $html);
        $this->assertStringContainsString(route('found-children.create'), $html);
    }

    // ── DoD S-03 #2 — liên kết missing_children được điền sẵn ────────────────

    public function test_found_form_prefills_link_from_match_query_param(): void
    {
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);
        $child = MissingChild::factory()->create(['status' => 'APPROVED']);

        Livewire::actingAs($user)
            ->withQueryParams(['match' => $child->case_code])
            ->test('found-children.create-found-form')
            ->assertSet('matchedMissingId', $child->id)
            ->assertSet('matchedCaseCode', $child->case_code)
            ->assertSee($child->case_code);
    }

    public function test_found_form_ignores_invalid_or_closed_match_param(): void
    {
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);

        // Mã sai định dạng.
        Livewire::actingAs($user)
            ->withQueryParams(['match' => 'not-a-case-code'])
            ->test('found-children.create-found-form')
            ->assertSet('matchedMissingId', null);

        // Hồ sơ đã đóng → không cho liên kết.
        $closed = MissingChild::factory()->create(['status' => MissingChild::STATUS_REUNITED]);

        Livewire::actingAs($user)
            ->withQueryParams(['match' => $closed->case_code])
            ->test('found-children.create-found-form')
            ->assertSet('matchedMissingId', null);
    }

    public function test_submitting_with_prefilled_match_records_pending_manual_link(): void
    {
        $province = Province::factory()->create(['is_active' => 1]);
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);
        $child = MissingChild::factory()->create(['status' => 'APPROVED']);

        Livewire::actingAs($user)
            ->withQueryParams(['match' => $child->case_code])
            ->test('found-children.create-found-form')
            ->set([
                'province_id' => (string) $province->id,
                'found_date' => now()->toDateString(),
                'gender_estimate' => 'UNKNOWN',
                'special_marks' => str_repeat('a', 15),
                'agreed' => true,
                'agreedDataPolicy' => true,
                'dataUseConsent' => true,
                'lat' => '21.0285',
                'lng' => '105.8542',
            ])
            ->call('submit')
            ->assertHasNoErrors();

        $found = FoundChild::firstOrFail();
        $link = FaceMatchResult::where('found_id', $found->id)->firstOrFail();

        $this->assertSame($child->id, (int) $link->missing_id);
        $this->assertSame('MANUAL', $link->source, 'Liên kết do người báo tin chọn, không phải kết luận AI.');
        $this->assertSame('PENDING', $link->status, 'Liên kết phải chờ xác minh, không được coi là đã khớp.');
        $this->assertFalse((bool) $link->is_matched);
    }

    public function test_clearing_the_link_prevents_it_from_being_recorded(): void
    {
        $province = Province::factory()->create(['is_active' => 1]);
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);
        $child = MissingChild::factory()->create(['status' => 'APPROVED']);

        Livewire::actingAs($user)
            ->withQueryParams(['match' => $child->case_code])
            ->test('found-children.create-found-form')
            ->call('clearMatchedMissingChild')
            ->assertSet('matchedMissingId', null)
            ->set([
                'province_id' => (string) $province->id,
                'found_date' => now()->toDateString(),
                'gender_estimate' => 'UNKNOWN',
                'special_marks' => str_repeat('a', 15),
                'agreed' => true,
                'agreedDataPolicy' => true,
                'dataUseConsent' => true,
                'lat' => '21.0285',
                'lng' => '105.8542',
            ])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, FoundChild::count());
        $this->assertSame(0, FaceMatchResult::count(), 'Đã bỏ liên kết thì không được ghi bản ghi nào.');
    }
}
