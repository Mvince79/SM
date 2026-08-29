<?php

namespace Tests\Unit;

use App\Models\FaceMatchResult;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Contracts\NotificationStrategy;
use App\Services\Notification\NotificationPolicyResolver;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationPolicyResolverTest extends TestCase
{
    public function test_same_event_renders_different_content_for_family_and_admin(): void
    {
        $resolver = app(NotificationPolicyResolver::class);
        $event = $this->faceMatchConfirmedEvent();

        $family = $resolver->resolve($event, new User(['role' => 'USER']));
        $admin = $resolver->resolve($event, new User(['role' => 'ADMIN']));

        $this->assertNotSame($family['title'], $admin['title']);
        $this->assertNotSame($family['body'], $admin['body']);

        foreach ([$family, $admin] as $content) {
            $this->assertSame(['title', 'body', 'action_url'], array_keys($content));
            $this->assertSame(route('missing.detail', ['id' => 42]), $content['action_url']);
        }
    }

    public function test_moderator_uses_admin_strategy(): void
    {
        $resolver = app(NotificationPolicyResolver::class);
        $event = $this->faceMatchConfirmedEvent();

        $admin = $resolver->resolve($event, new User(['role' => 'ADMIN']));
        $moderator = $resolver->resolve($event, new User(['role' => 'MODERATOR']));

        $this->assertSame($admin, $moderator);
    }

    public function test_new_event_strategy_can_be_registered_without_changing_resolver(): void
    {
        config()->set('notification-policies.strategies.CUSTOM_EVENT.USER', CustomNotificationStrategy::class);

        $event = new NotificationEvent(['event_type' => 'CUSTOM_EVENT']);
        $content = app(NotificationPolicyResolver::class)
            ->resolve($event, new User(['role' => 'USER']));

        $this->assertSame([
            'title' => 'Custom title',
            'body' => 'Custom body',
            'action_url' => null,
        ], $content);
    }

    /**
     * Đổi hành vi có chủ ý: trước đây ném NotificationStrategyNotFoundException.
     *
     * Với hệ thống an toàn trẻ em, một role bị quên khai báo mà làm người nhận
     * KHÔNG nhận được gì thì tệ hơn là nhận một thông báo tối thiểu kèm liên kết.
     * Sai sót cấu hình vẫn lộ ra qua Log::warning (xem test kế tiếp).
     */
    public function test_it_falls_back_to_generic_content_for_an_unregistered_role(): void
    {
        $content = app(NotificationPolicyResolver::class)->resolve(
            $this->faceMatchConfirmedEvent(),
            new User(['role' => 'UNREGISTERED_ROLE'])
        );

        $this->assertSame(['title', 'body', 'action_url'], array_keys($content));
        $this->assertNotSame('', $content['title']);
        $this->assertNotSame('', $content['body']);
    }

    public function test_it_logs_a_warning_when_content_is_not_declared(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Thiếu khai báo nội dung thông báo', [
                'event_type' => NotificationEvent::TYPE_FACE_MATCH_CONFIRMED,
                'role' => 'UNREGISTERED_ROLE',
            ]);

        app(NotificationPolicyResolver::class)->resolve(
            $this->faceMatchConfirmedEvent(),
            new User(['role' => 'UNREGISTERED_ROLE'])
        );
    }

    public function test_it_renders_declared_content_from_config(): void
    {
        config()->set('notification-policies.content.DECLARED_EVENT.*', [
            'title' => 'Hồ sơ :case_code',
            'body' => 'Nội dung cho :full_name.',
            'url' => null,
        ]);

        $event = new NotificationEvent([
            'event_type' => 'DECLARED_EVENT',
            'payload' => ['case_code' => 'MC-2026-00042', 'full_name' => 'Nguyễn Văn An'],
        ]);

        $content = app(NotificationPolicyResolver::class)
            ->resolve($event, new User(['role' => 'USER']));

        $this->assertSame('Hồ sơ MC-2026-00042', $content['title']);
        $this->assertSame('Nội dung cho Nguyễn Văn An.', $content['body']);
    }

    public function test_it_drops_placeholders_that_have_no_data(): void
    {
        config()->set('notification-policies.content.DECLARED_EVENT.*', [
            'title' => 'Lý do: :reason',
            'body' => 'Hồ sơ :case_code bị từ chối vì :reason.',
            'url' => null,
        ]);

        $event = new NotificationEvent([
            'event_type' => 'DECLARED_EVENT',
            'payload' => ['case_code' => 'MC-2026-00042'],
        ]);

        $content = app(NotificationPolicyResolver::class)
            ->resolve($event, new User(['role' => 'USER']));

        // Không được để lộ ":reason" ra mặt người dùng khi thiếu dữ liệu.
        $this->assertStringNotContainsString(':reason', $content['title']);
        $this->assertStringNotContainsString(':reason', $content['body']);
        $this->assertStringContainsString('MC-2026-00042', $content['body']);
    }

    private function faceMatchConfirmedEvent(): NotificationEvent
    {
        $case = new MissingChild([
            'full_name' => 'Nguyễn Văn An',
            'case_code' => 'MC-2026-00042',
        ]);
        $case->id = 42;

        $match = new FaceMatchResult([
            'missing_id' => 42,
            'found_id' => 8,
            'score' => 0.9345,
            'status' => 'CONFIRMED',
        ]);
        $match->id = 99;
        $match->setRelation('missingChild', $case);

        $event = new NotificationEvent([
            'event_type' => NotificationEvent::TYPE_FACE_MATCH_CONFIRMED,
        ]);
        $event->setRelation('subject', $match);

        return $event;
    }
}

class CustomNotificationStrategy implements NotificationStrategy
{
    public function render(NotificationEvent $event, User $recipient): array
    {
        return [
            'title' => 'Custom title',
            'body' => 'Custom body',
            'action_url' => null,
        ];
    }
}
