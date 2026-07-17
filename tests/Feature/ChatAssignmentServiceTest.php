<?php

namespace Tests\Feature;

use App\Models\AdminChatAssignment;
use App\Models\User;
use App\Services\ChatAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ChatAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_balances_new_chats_across_online_admins(): void
    {
        $adminA = $this->user(['role' => 'ADMIN', 'last_active_at' => now()]);
        $adminB = $this->user(['role' => 'MODERATOR', 'last_active_at' => now()]);
        $requesterA = $this->user();
        $requesterB = $this->user();
        $service = app(ChatAssignmentService::class);

        $first = $service->assign($requesterA);
        $second = $service->assign($requesterB);

        $this->assertNotSame($first->admin_user_id, $second->admin_user_id);
        $this->assertEqualsCanonicalizing(
            [$adminA->id, $adminB->id],
            [$first->admin_user_id, $second->admin_user_id]
        );
    }

    public function test_it_returns_the_existing_active_assignment_for_a_requester(): void
    {
        $this->user(['role' => 'ADMIN', 'last_active_at' => now()]);
        $requester = $this->user();
        $service = app(ChatAssignmentService::class);

        $first = $service->assign($requester);
        $second = $service->assign($requester);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, AdminChatAssignment::count());
    }

    public function test_it_ignores_offline_and_inactive_admins(): void
    {
        $this->user(['role' => 'ADMIN', 'last_active_at' => now()->subMinutes(6)]);
        $this->user(['role' => 'ADMIN', 'status' => 'BANNED', 'last_active_at' => now()]);
        $requester = $this->user();

        $this->expectException(RuntimeException::class);
        app(ChatAssignmentService::class)->assign($requester);
    }

    private function user(array $overrides = []): User
    {
        return User::query()->create(array_merge([
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => 'USER',
            'status' => 'ACTIVE',
        ], $overrides));
    }
}
