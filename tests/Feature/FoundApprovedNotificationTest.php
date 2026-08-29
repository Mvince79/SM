<?php

namespace Tests\Feature;

use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundApprovedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeReporter(): User
    {
        return User::factory()->create(['role' => 'USER']);
    }

    private function makeFoundChild(User $reporter): FoundChild
    {
        return FoundChild::query()->create([
            'user_id' => $reporter->id,
            'province_id' => Province::factory()->create()->id,
            'found_date' => now()->toDateString(),
            'status' => FoundChild::STATUS_PENDING,
        ]);
    }

    public function test_approving_found_child_without_match_notifies_only_reporter(): void
    {
        $reporter = $this->makeReporter();
        $found = $this->makeFoundChild($reporter);

        $found->update([
            'status' => FoundChild::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $reporter->id,
        ]);

        $event = NotificationEvent::where('event_type', NotificationEvent::TYPE_FOUND_APPROVED)->first();
        $this->assertNotNull($event);
        $this->assertSame($found->id, $event->subject_id);

        $recipientIds = NotificationRecipient::where('notification_event_id', $event->id)
            ->pluck('user_id')
            ->all();

        // Chưa có khớp nào → CHỈ người báo tin nhận, không ai khác.
        $this->assertCount(1, $recipientIds);
        $this->assertContains($reporter->id, $recipientIds);
    }

    public function test_approving_found_child_with_confirmed_match_notifies_reporter_and_family(): void
    {
        $reporter = $this->makeReporter();
        $found = $this->makeFoundChild($reporter);

        $family = User::factory()->create(['role' => 'USER']);
        $missing = MissingChild::factory()->create([
            'user_id' => $family->id,
            'status' => MissingChild::STATUS_APPROVED,
        ]);

        // Kết quả khớp CONFIRMED liên kết FoundChild này với MissingChild của gia đình.
        FaceMatchResult::query()->create([
            'missing_id' => $missing->id,
            'found_id' => $found->id,
            'score' => 0.93,
            'is_matched' => true,
            'confidence_level' => 'HIGH',
            'source' => 'REALTIME',
            'status' => 'CONFIRMED',
            'confirmed_by' => $family->id,
            'confirmed_at' => now(),
        ]);

        $found->update([
            'status' => FoundChild::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $reporter->id,
        ]);

        $event = NotificationEvent::where('event_type', NotificationEvent::TYPE_FOUND_APPROVED)->first();
        $this->assertNotNull($event);

        $recipientIds = NotificationRecipient::where('notification_event_id', $event->id)
            ->pluck('user_id')
            ->all();

        // Đã có khớp CONFIRMED → người báo tin VÀ gia đình đều nhận.
        $this->assertCount(2, $recipientIds);
        $this->assertContains($reporter->id, $recipientIds);
        $this->assertContains($family->id, $recipientIds);
    }
}
