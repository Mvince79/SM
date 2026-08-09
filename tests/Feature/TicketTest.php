<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * D-01 — Feature Test: Ticket hỗ trợ (Nhóm M).
 *
 * ⚠️ [ASSUMPTION] Chưa có source StoreSupportTicketRequest thật, các rule validate
 * dưới đây suy từ SupportTicketController::store() + mô tả migration Sprint 4 (M-01):
 *   category: required, in:BUG,COMPLAINT,FEEDBACK,FRAUD_SUSPECT
 *   subject: required, string
 *   description: required, string
 *   priority: nullable, in:LOW,MEDIUM,HIGH (mặc định MEDIUM nếu bỏ trống)
 * Nếu Request thật khác (ví dụ subject có max length, description có min length),
 * chỉnh lại phần dataProvider/validPayload() cho khớp.
 */
class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'category' => 'BUG',
            'subject' => 'Không đăng được ảnh',
            'description' => 'Ấn nút đăng ảnh nhưng không thấy phản hồi gì.',
            'priority' => 'HIGH',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login_for_all_support_routes(): void
    {
        $this->get(route('support.index'))->assertRedirect();
        $this->get(route('support.create'))->assertRedirect();
        $this->post(route('support.store'), $this->validPayload())->assertRedirect();
    }

    public function test_authenticated_user_can_view_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('support.create'));

        $response->assertOk();
        $response->assertViewIs('support.create');
    }

    public function test_user_can_submit_a_support_ticket(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('support.store'), $this->validPayload());

        $response->assertRedirect(route('support.index'));
        $response->assertSessionHas('ticket_submitted');

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'category' => 'BUG',
            'subject' => 'Không đăng được ảnh',
            'priority' => 'HIGH',
            'status' => 'OPEN',
        ]);
    }

    public function test_ticket_priority_defaults_to_medium_when_not_provided(): void
    {
        $user = User::factory()->create();

        $payload = $this->validPayload();
        unset($payload['priority']);

        $this->actingAs($user)->post(route('support.store'), $payload);

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'priority' => 'MEDIUM',
        ]);
    }

    #[DataProvider('invalidPayloadProvider')]
    public function test_ticket_submission_fails_with_invalid_payload(array $overrides, string $invalidField): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('support.store'), $this->validPayload($overrides));

        $response->assertSessionHasErrors([$invalidField]);
        $this->assertDatabaseCount('support_tickets', 0);
    }

    public static function invalidPayloadProvider(): array
    {
        return [
            'missing category' => [['category' => null], 'category'],
            'invalid category value' => [['category' => 'NOT_A_CATEGORY'], 'category'],
            'missing subject' => [['subject' => null], 'subject'],
            'missing description' => [['description' => null], 'description'],
            'invalid priority value' => [['priority' => 'URGENT'], 'priority'],
        ];
    }

    public function test_user_only_sees_own_tickets_in_index(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $myTicket = SupportTicket::factory()->create(['user_id' => $user->id, 'subject' => 'Vé của tôi']);
        SupportTicket::factory()->create(['user_id' => $otherUser->id, 'subject' => 'Vé của người khác']);

        $response = $this->actingAs($user)->get(route('support.index'));

        $response->assertOk();
        $response->assertViewHas('tickets', function ($tickets) use ($myTicket) {
            return $tickets->count() === 1 && $tickets->first()->is($myTicket);
        });
    }
}
