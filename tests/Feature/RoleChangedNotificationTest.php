<?php

namespace Tests\Feature;

use App\Jobs\SendFcmNotificationJob;
use App\Mail\DynamicNotificationMail;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\UserRoleChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RoleChangedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_change_creates_in_app_notification_and_queues_email(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $user = User::factory()->create(['role' => 'USER']);

        $changedUser = app(UserRoleChangeService::class)
            ->changeRole($user, 'THIRD_PARTY_VERIFIER', $admin);

        $this->assertSame('THIRD_PARTY_VERIFIER', $changedUser->role);

        $event = NotificationEvent::query()->sole();
        $this->assertSame(NotificationEvent::TYPE_ROLE_CHANGED, $event->event_type);
        $this->assertSame($user->getKey(), $event->subject_id);
        $this->assertSame($admin->getKey(), $event->triggered_by_user_id);
        $this->assertSame([
            'old_role' => 'USER',
            'new_role' => 'THIRD_PARTY_VERIFIER',
        ], $event->payload);

        $recipient = NotificationRecipient::query()->sole();
        $this->assertSame($changedUser->getKey(), $recipient->user_id);
        $this->assertSame('THIRD_PARTY_VERIFIER', $recipient->role_at_time);
        $this->assertSame('Quyền truy cập tài khoản đã thay đổi', $recipient->rendered_title);
        $this->assertStringContainsString(
            'Người dùng sang Bên thứ ba xác minh',
            $recipient->rendered_body,
        );
        $this->assertStringContainsString('đăng nhập lại', $recipient->rendered_body);
        $this->assertSame(route('auth.login'), $recipient->action_url);
        $this->assertNull($recipient->read_at);

        Queue::assertPushed(
            SendFcmNotificationJob::class,
            fn (SendFcmNotificationJob $job): bool => $job->notificationRecipientId === $recipient->getKey(),
        );
        Mail::assertQueued(
            DynamicNotificationMail::class,
            fn (DynamicNotificationMail $mail): bool => $mail->recipient->getKey() === $recipient->getKey()
                && $mail->hasTo($changedUser->email),
        );

        $mail = new DynamicNotificationMail($recipient);
        $html = html_entity_decode(strip_tags($mail->render()));

        $this->assertSame('emails.notifications.role-changed', $mail->content()->markdown);
        $this->assertStringContainsString('Đăng nhập lại', $html);
        $this->assertStringContainsString('TìmTrẻ.vn', $html);
    }

    public function test_unchanged_role_does_not_create_or_send_notification(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $user = User::factory()->create(['role' => 'VOLUNTEER']);

        $unchangedUser = app(UserRoleChangeService::class)
            ->changeRole($user, 'VOLUNTEER', $admin);

        $this->assertTrue($user->is($unchangedUser));
        $this->assertDatabaseCount('notification_events', 0);
        $this->assertDatabaseCount('notification_recipients', 0);
        Queue::assertNotPushed(SendFcmNotificationJob::class);
        Mail::assertNothingQueued();
    }
}
