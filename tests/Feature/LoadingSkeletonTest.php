<?php

namespace Tests\Feature;

use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LoadingSkeletonTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_page_renders_tile_loading_skeleton(): void
    {
        $this->get(route('map.index'))
            ->assertOk()
            ->assertSee('data-map-loading-skeleton', false);
    }

    public function test_ai_match_skeleton_component_renders_requested_rows(): void
    {
        $html = Blade::render(
            '<x-skeletons.ai-match-list :rows="3" data-ai-match-list-skeleton />'
        );

        $this->assertStringContainsString('data-ai-match-list-skeleton', $html);
        $this->assertSame(3, substr_count($html, 'rounded-xl border border-slate-100 bg-white p-3'));
    }

    public function test_notification_dropdown_starts_with_skeleton_and_loads_json(): void
    {
        $user = User::factory()->create();
        $event = NotificationEvent::query()->create([
            'event_type' => NotificationEvent::TYPE_ROLE_CHANGED,
            'subject_type' => $user->getMorphClass(),
            'subject_id' => $user->getKey(),
            'occurred_at' => now(),
        ]);
        $notification = NotificationRecipient::query()->create([
            'notification_event_id' => $event->getKey(),
            'user_id' => $user->getKey(),
            'role_at_time' => $user->role,
            'rendered_title' => 'Thông báo kiểm thử',
            'rendered_body' => 'Nội dung kiểm thử skeleton.',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-notification-dropdown-skeleton', false);

        $this->actingAs($user)
            ->getJson(route('notifications.dropdown'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.id', $notification->getKey())
            ->assertJsonPath('notifications.0.title', 'Thông báo kiểm thử')
            ->assertJsonCount(1, 'notifications');
    }

    public function test_notification_center_defers_data_behind_skeleton(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notifications.center'))
            ->assertOk()
            ->assertSee('wire:init="loadNotifications"', false)
            ->assertSee('data-notification-center-skeleton', false);
    }
}
