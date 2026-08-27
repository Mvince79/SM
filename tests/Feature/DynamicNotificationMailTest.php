<?php

namespace Tests\Feature;

use App\Mail\DynamicNotificationMail;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DynamicNotificationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_four_event_templates_render_role_based_content_for_three_roles(): void
    {
        Mail::fake();
        $subject = User::factory()->create();
        $events = [
            NotificationEvent::TYPE_FACE_MATCH_CONFIRMED => 'emails.notifications.face-match-confirmed',
            NotificationEvent::TYPE_CASE_APPROVED => 'emails.notifications.case-approved',
            NotificationEvent::TYPE_CLUE_VERIFIED => 'emails.notifications.clue-verified',
            NotificationEvent::TYPE_HANDOVER_READY => 'emails.notifications.handover-ready',
        ];
        $roles = [
            'USER' => 'Gia đình / Người dùng',
            'VOLUNTEER' => 'Tình nguyện viên',
            'ADMIN' => 'Quản trị viên',
        ];

        foreach ($events as $eventType => $expectedView) {
            $event = NotificationEvent::query()->create([
                'event_type' => $eventType,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'payload' => ['source' => 'mail-template-test'],
                'occurred_at' => now(),
            ]);

            foreach ($roles as $role => $roleLabel) {
                $user = User::factory()->create(['role' => $role]);
                $title = "{$eventType} title for {$role}";
                $body = "{$eventType} body for {$role}";
                $recipient = NotificationRecipient::query()->create([
                    'notification_event_id' => $event->getKey(),
                    'user_id' => $user->getKey(),
                    'role_at_time' => $role,
                    'rendered_title' => $title,
                    'rendered_body' => $body,
                    'action_url' => url("/notifications/{$event->getKey()}"),
                ]);
                $mail = new DynamicNotificationMail($recipient);
                $html = html_entity_decode(strip_tags($mail->render()));

                $this->assertSame('notifications', $mail->queue);
                $this->assertSame($title, $mail->envelope()->subject);
                $this->assertSame($expectedView, $mail->content()->markdown);
                $this->assertStringContainsString($user->full_name, $html);
                $this->assertStringContainsString($roleLabel, $html);
                $this->assertStringContainsString($title, $html);
                $this->assertStringContainsString($body, $html);
                $this->assertStringContainsString('TìmTrẻ.vn', $html);
                $this->assertStringNotContainsString('{{', $html);

                Mail::to($user->email)->queue($mail);
            }
        }

        Mail::assertQueued(DynamicNotificationMail::class, 12);
    }

    public function test_province_case_approved_event_uses_case_approved_template(): void
    {
        $subject = User::factory()->create();
        $recipient = $this->recipientFor(
            $subject,
            NotificationEvent::TYPE_CASE_APPROVED_IN_PROVINCE,
        );

        $this->assertSame(
            'emails.notifications.case-approved',
            (new DynamicNotificationMail($recipient))->content()->markdown,
        );
    }

    private function recipientFor(User $subject, string $eventType): NotificationRecipient
    {
        $event = NotificationEvent::query()->create([
            'event_type' => $eventType,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'occurred_at' => now(),
        ]);
        $user = User::factory()->create();

        return NotificationRecipient::query()->create([
            'notification_event_id' => $event->getKey(),
            'user_id' => $user->getKey(),
            'role_at_time' => 'USER',
            'rendered_title' => 'Case approved',
            'rendered_body' => 'A case in your province was approved.',
            'action_url' => url('/missing/1'),
        ]);
    }
}
