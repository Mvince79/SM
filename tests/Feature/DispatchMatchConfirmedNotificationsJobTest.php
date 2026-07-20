<?php

namespace Tests\Feature;

use App\Jobs\DispatchMatchConfirmedNotificationsJob;
use App\Jobs\SendFcmNotificationJob;
use App\Mail\RoleBasedNotificationMail;
use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\HandoverPoint;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\Province;
use App\Models\ThirdPartyVerifierProfile;
use App\Models\User;
use App\Services\Notification\ConfirmedMatchRecipientFinder;
use App\Services\Notification\NotificationDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchMatchConfirmedNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_match_creates_role_based_recipients_and_queues_fcm_and_email(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);
        $match = $this->confirmedMatchWithRecipients();
        $job = new DispatchMatchConfirmedNotificationsJob($match->id);

        $job->handle(
            app(ConfirmedMatchRecipientFinder::class),
            app(NotificationDeliveryService::class),
        );

        $event = NotificationEvent::query()->sole();
        $recipients = NotificationRecipient::query()->with('user')->get();

        $this->assertSame(NotificationEvent::TYPE_FACE_MATCH_CONFIRMED, $event->event_type);
        $this->assertCount(4, $recipients);
        $this->assertCount(3, $recipients->pluck('rendered_title')->unique());
        $this->assertEqualsCanonicalizing(
            ['USER', 'USER', 'THIRD_PARTY_VERIFIER', 'ADMIN'],
            $recipients->pluck('role_at_time')->all(),
        );

        Queue::assertPushed(SendFcmNotificationJob::class, 4);
        Mail::assertQueued(RoleBasedNotificationMail::class, 4);

        $job->handle(
            app(ConfirmedMatchRecipientFinder::class),
            app(NotificationDeliveryService::class),
        );

        $this->assertSame(1, NotificationEvent::count());
        $this->assertSame(4, NotificationRecipient::count());
        Queue::assertPushed(SendFcmNotificationJob::class, 4);
        Mail::assertQueued(RoleBasedNotificationMail::class, 4);
    }

    public function test_job_uses_notification_queue_and_retry_policy(): void
    {
        $job = new DispatchMatchConfirmedNotificationsJob(123);

        $this->assertSame('notifications', $job->queue);
        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 180, 600], $job->backoff);

        $fcmJob = new SendFcmNotificationJob(456);
        $this->assertSame('notifications', $fcmJob->queue);
        $this->assertSame(3, $fcmJob->tries);
        $this->assertSame([60, 180, 600], $fcmJob->backoff);
    }

    private function confirmedMatchWithRecipients(): FaceMatchResult
    {
        $province = Province::factory()->create();
        $family = User::factory()->create();
        $reporter = User::factory()->create();
        $admin = User::factory()->create([
            'role' => 'ADMIN',
            'last_active_at' => now(),
        ]);
        User::factory()->create([
            'role' => 'ADMIN',
            'last_active_at' => now()->subMinutes(6),
        ]);
        $verifier = User::factory()->create([
            'role' => 'THIRD_PARTY_VERIFIER',
        ]);
        $handoverPoint = HandoverPoint::query()->create([
            'name' => 'Công an phường thử nghiệm',
            'type' => 'POLICE_STATION',
            'address' => 'Địa chỉ thử nghiệm',
            'province_id' => $province->id,
            'lat' => 10.7769,
            'lng' => 106.7009,
            'responsible_org_name' => 'Công an',
            'legal_authority_note' => 'Điểm tiếp nhận được xác minh',
            'is_active' => true,
        ]);
        ThirdPartyVerifierProfile::query()->create([
            'user_id' => $verifier->id,
            'organization_type' => 'POLICE',
            'organization_name' => 'Công an phường thử nghiệm',
            'handover_point_id' => $handoverPoint->id,
            'verified_by_admin_id' => $admin->id,
            'verified_at' => now(),
        ]);

        $missing = MissingChild::withoutEvents(fn () => MissingChild::query()->create([
            'user_id' => $family->id,
            'case_code' => 'MC-2026-90001',
            'full_name' => 'Nguyễn Văn An',
            'gender' => 'MALE',
            'province_id' => $province->id,
            'missing_date' => now()->subDay()->toDateString(),
            'contact_name' => 'Nguyễn Văn A',
            'contact_phone' => '0905123456',
            'status' => 'FOUND',
        ]));
        $found = FoundChild::query()->create([
            'user_id' => $reporter->id,
            'province_id' => $province->id,
            'found_date' => now()->toDateString(),
            'status' => 'MATCHED',
        ]);

        return FaceMatchResult::query()->create([
            'missing_id' => $missing->id,
            'found_id' => $found->id,
            'score' => 0.9345,
            'is_matched' => true,
            'confidence_level' => 'HIGH',
            'source' => 'REALTIME',
            'status' => 'CONFIRMED',
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
        ]);
    }
}
