<?php

namespace Tests\Feature;

use App\Http\Livewire\Chat\ChatContainer;
use App\Models\AdminChatAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class DirectUserChatRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_user_chat_routes_are_not_registered(): void
    {
        $this->assertFalse(Route::has('chat.dev'));
        $this->assertFalse(Route::has('chat.index'));
        $this->assertFalse(Route::has('chat.attachment'));
    }

    public function test_controlled_handover_and_admin_chat_routes_remain_registered(): void
    {
        $this->assertTrue(Route::has('verifier.handover.confirm'));
        $this->assertTrue(Route::has('admin-chat.attachment'));
        $this->assertTrue(Route::has('support.index'));
    }

    public function test_missing_child_page_does_not_offer_direct_chat(): void
    {
        $this->assertStringNotContainsString(
            'Chat với người đăng tin',
            file_get_contents(resource_path('views/missing/detail.blade.php')),
        );
        $this->assertStringNotContainsString(
            "Livewire.dispatch('openChat'",
            file_get_contents(resource_path('views/missing/detail.blade.php')),
        );
    }

    public function test_chat_container_rejects_a_user_without_an_active_admin_assignment(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        Livewire::actingAs($currentUser)
            ->test(ChatContainer::class)
            ->dispatch('openChat', userId: $otherUser->id, missingId: null)
            ->assertSet('selectedUserId', null);
    }

    public function test_chat_container_keeps_assigned_admin_support_chat_working(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $requester = User::factory()->create();
        AdminChatAssignment::query()->create([
            'admin_user_id' => $admin->id,
            'requester_user_id' => $requester->id,
            'status' => AdminChatAssignment::STATUS_ACTIVE,
            'assigned_at' => now(),
        ]);

        Livewire::actingAs($requester)
            ->test(ChatContainer::class)
            ->dispatch('openChat', userId: $admin->id, missingId: null)
            ->assertSet('selectedUserId', $admin->id);
    }
}
