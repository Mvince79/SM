<?php

namespace Tests\Feature;

use App\Jobs\DispatchProvinceCaseApprovedNotificationsJob;
use App\Jobs\SendFcmNotificationJob;
use App\Mail\DynamicNotificationMail;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\Province;
use App\Models\User;
use App\Services\Notification\NotificationDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProvinceCaseApprovedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_transition_dispatches_province_notification_once(): void
    {
        Queue::fake([DispatchProvinceCaseApprovedNotificationsJob::class]);
        $province = Province::factory()->create();
        $case = $this->case($province, 'PENDING');

        $case->update([
            'status' => 'APPROVED',
            'approved_at' => now(),
        ]);

        Queue::assertPushed(
            DispatchProvinceCaseApprovedNotificationsJob::class,
            fn (DispatchProvinceCaseApprovedNotificationsJob $job): bool => $job->missingChildId === $case->id,
        );

        Queue::assertPushed(DispatchProvinceCaseApprovedNotificationsJob::class, 1);
    }

    /**
     * Chính sách kênh cho thông báo cộng đồng: chuông + push, KHÔNG email.
     *
     * Trước đây mọi event đều gửi email; với event phát cho toàn bộ người dùng
     * một tỉnh, việc đó là con đường ngắn nhất để bị đánh dấu spam. Push vẫn
     * được giữ vì đúng mục đích tính năng "theo dõi khu vực".
     */
    public function test_only_active_users_in_the_case_province_receive_bell_and_push_but_no_email(): void
    {
        Mail::fake();
        Queue::fake([SendFcmNotificationJob::class]);
        $provinceX = Province::factory()->create([
            'name' => 'Tỉnh X',
            'code' => 'VN-PXA',
            'short_code' => 'PXA',
        ]);
        $provinceY = Province::factory()->create([
            'name' => 'Tỉnh Y',
            'code' => 'VN-PYB',
            'short_code' => 'PYB',
        ]);
        $localUser = User::factory()->create([
            'province_id' => $provinceX->id,
            'role' => 'USER',
            'status' => 'ACTIVE',
        ]);
        $localVolunteer = User::factory()->create([
            'province_id' => $provinceX->id,
            'role' => 'VOLUNTEER',
            'status' => 'ACTIVE',
        ]);
        User::factory()->create([
            'province_id' => $provinceX->id,
            'role' => 'USER',
            'status' => 'BANNED',
        ]);
        User::factory()->create([
            'province_id' => $provinceX->id,
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);
        User::factory()->create([
            'province_id' => $provinceY->id,
            'role' => 'USER',
            'status' => 'ACTIVE',
        ]);
        $case = $this->case($provinceX, 'APPROVED', $provinceY);
        $job = new DispatchProvinceCaseApprovedNotificationsJob($case->id);

        $job->handle(app(NotificationDeliveryService::class));

        $event = NotificationEvent::query()->sole();
        $recipientUserIds = NotificationRecipient::query()
            ->pluck('user_id')
            ->all();

        $this->assertSame(NotificationEvent::TYPE_CASE_APPROVED_IN_PROVINCE, $event->event_type);
        $this->assertSame($provinceX->id, $event->payload['province_id']);
        $this->assertEqualsCanonicalizing(
            [$localUser->id, $localVolunteer->id],
            $recipientUserIds,
        );
        Queue::assertPushed(SendFcmNotificationJob::class, 2);
        Mail::assertNothingQueued();

        $job->handle(app(NotificationDeliveryService::class));

        $this->assertSame(1, NotificationEvent::count());
        $this->assertSame(2, NotificationRecipient::count());
        Queue::assertPushed(SendFcmNotificationJob::class, 2);
        Mail::assertNothingQueued();
    }

    public function test_job_uses_notification_queue_and_retry_policy(): void
    {
        $job = new DispatchProvinceCaseApprovedNotificationsJob(123);

        $this->assertSame('notifications', $job->queue);
        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 180, 600], $job->backoff);
        $this->assertTrue($job->afterCommit);
    }

    private function case(
        Province $province,
        string $status,
        ?Province $ownerProvince = null,
    ): MissingChild {
        $owner = User::factory()->create([
            'province_id' => ($ownerProvince ?? $province)->id,
        ]);

        return MissingChild::withoutEvents(fn (): MissingChild => MissingChild::query()->create([
            'user_id' => $owner->id,
            'case_code' => 'MC-2026-70001',
            'full_name' => 'Nguyễn Văn An',
            'gender' => 'MALE',
            'province_id' => $province->id,
            'missing_date' => now()->subDay()->toDateString(),
            'contact_name' => 'Nguyễn Văn A',
            'contact_phone' => '0905123456',
            'status' => $status,
            'approved_at' => $status === 'APPROVED' ? now() : null,
        ]));
    }
}
