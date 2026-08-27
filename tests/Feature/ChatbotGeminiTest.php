<?php

namespace Tests\Feature;

use App\Models\HandoverPoint;
use App\Models\MissingChild;
use App\Models\Province;
use App\Services\Gemini\ChatbotTools;
use App\Services\HandoverPointFinderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * Bước 7 — Kiểm thử luồng Gemini (function calling) bằng Http::fake,
 * không cần key thật. Cover 5 dạng câu hỏi + ràng buộc quyền riêng tư.
 */
class ChatbotGeminiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Bật key giả để ChatbotGeminiService thực sự gọi (được Http::fake chặn).
        config(['gemini.api_key' => 'test-key']);

        $province = Province::create([
            'name' => 'Cần Thơ',
            'code' => 'CT',
            'short_code' => 'CT',
            'region' => 'NAM',
            'is_active' => 1,
        ]);

        $user = \App\Models\User::factory()->create();

        $case = MissingChild::create([
            'user_id' => $user->id,
            'case_code' => 'MC-2026-00001',
            'full_name' => 'Nguyễn Văn An',
            'age_estimate' => 8,
            'gender' => 'MALE',
            'province_id' => $province->id,
            'missing_date' => '2026-01-15',
            'circumstances' => 'Lạc gia đình khi đi chợ.',
            // Dữ liệu NHẠY CẢM — tuyệt đối không được trả về qua chatbot.
            'contact_name' => 'Nguyễn Văn B (gia đình)',
            'contact_phone' => '0901234567',
        ]);
        // status là guarded → đặt sau khi tạo.
        $case->forceFill(['status' => MissingChild::STATUS_APPROVED])->save();
    }

    protected function tearDown(): void
    {
        // Trả key về rỗng để các test class khác (intent resolver cũ) luôn fallback,
        // không bị ảnh hưởng bởi config('gemini.api_key') giả lập ở setUp.
        config(['gemini.api_key' => '']);
        parent::tearDown();
    }

    public function test_stats_question_calls_getProjectStats(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['candidates' => [['content' => ['parts' => [['functionCall' => ['name' => 'getProjectStats', 'args' => []]]]]]]])
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'Tổng quan: hệ thống đang có 1 hồ sơ đang tìm kiếm tại 1 tỉnh.']]]]]]),
        ]);

        $response = $this->postJson('/api/v1/chatbot/message', [
            'message' => 'TìmTrẻ hiện có bao nhiêu hồ sơ và bao nhiêu tỉnh?',
            'lat' => 10.0,
            'lng' => 105.0,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent', 'GEMINI')
            ->assertJsonStructure(['data' => ['message', 'session_token']]);

        $this->assertNotEmpty($response->json('data.message'));
    }

    public function test_case_code_question_calls_getCaseByCode_and_does_not_leak_phone(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['candidates' => [['content' => ['parts' => [['functionCall' => ['name' => 'getCaseByCode', 'args' => ['case_code' => 'MC-2026-00001']]]]]]]])
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'Hồ sơ MC-2026-00001 thuộc trẻ Nguyễn Văn An, trạng thái Đang tìm kiếm.']]]]]]),
        ]);

        $response = $this->postJson('/api/v1/chatbot/message', [
            'message' => 'Cho tôi xem hồ sơ mã MC-2026-00001',
            'lat' => 10.0,
            'lng' => 105.0,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent', 'GEMINI')
            ->assertJsonFragment(['message' => $response->json('data.message')]);

        // Không được lộ SĐT/thông tin gia đình trong câu trả lời.
        $this->assertStringNotContainsString('0901234567', $response->json('data.message'));
    }

    public function test_nearest_handover_question_calls_getNearestHandoverPoints(): void
    {
        $points = collect([
            (new HandoverPoint)->forceFill([
                'id' => 1,
                'name' => 'Công an phường 1, Cà Mau',
                'type' => 'POLICE_STATION',
                'address' => 'Số 1, Cà Mau',
                'hotline_phone' => '0290000001',
                'lat' => 9.1769,
                'lng' => 105.152,
                'distance' => 1.2,
            ]),
        ]);

        $finder = Mockery::mock(HandoverPointFinderService::class);
        $finder->shouldReceive('findNearest')->once()->with(9.1769, 105.152, 3)->andReturn($points);
        $this->app->instance(HandoverPointFinderService::class, $finder);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['candidates' => [['content' => ['parts' => [['functionCall' => ['name' => 'getNearestHandoverPoints', 'args' => []]]]]]]])
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'Bạn hãy đưa bé đến Công an phường 1, Cà Mau (cách 1.2 km) nhé.']]]]]]),
        ]);

        $response = $this->postJson('/api/v1/chatbot/message', [
            'message' => 'Tôi ở Cà Mau tìm thấy 1 bé ở Sài Gòn thì đưa bé tới đâu gần nhất?',
            'lat' => 9.1769,
            'lng' => 105.152,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent', 'GEMINI')
            ->assertJsonPath('data.handover_points.0.name', 'Công an phường 1, Cà Mau')
            ->assertJsonPath('data.handover_points.0.distance_km', 1.2);
    }

    public function test_face_match_accuracy_answered_without_fabrication(): void
    {
        // Không gọi hàm — Gemini trả lời trực tiếp theo nguyên tắc "gợi ý/có thể".
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Kết quả AI đối chiếu khuôn mặt chỉ là GỢI Ý, CÓ THỂ trùng khớp — không chắc chắn 100%. Bạn nên kết hợp thêm thông tin khác.']]]]],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/chatbot/message', [
            'message' => 'AI nhận diện khuôn mặt có chắc chắn 100% không?',
            'lat' => 10.0,
            'lng' => 105.0,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent', 'GEMINI');

        $message = $response->json('data.message');
        $this->assertStringContainsString('gợi ý', mb_strtolower($message));
        $this->assertArrayNotHasKey('handover_points', $response->json('data'));
    }

    public function test_free_chat_returns_natural_answer_not_fallback_phrase(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Hôm nay miền Nam trời khá đẹp, nắng ấm và ít mưa đó!']]]]],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/chatbot/message', [
            'message' => 'Hôm nay thời tiết đẹp không?',
            'lat' => 10.0,
            'lng' => 105.0,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent', 'GEMINI');

        $this->assertStringNotContainsString('Tôi chưa hiểu rõ', $response->json('data.message'));
    }

    public function test_tools_do_not_leak_sensitive_data(): void
    {
        /** @var ChatbotTools $tools */
        $tools = $this->app->make(ChatbotTools::class);
        $result = $tools->call('getCaseByCode', ['case_code' => 'MC-2026-00001'], null, null);

        $this->assertTrue($result['found']);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('status_label', $result);
        // Bắt buộc: không rò rỉ SĐT / tên người thân / địa chỉ nhà.
        $this->assertArrayNotHasKey('contact_phone', $result);
        $this->assertArrayNotHasKey('contact_name', $result);
        $this->assertArrayNotHasKey('address_detail', $result);
    }
}
