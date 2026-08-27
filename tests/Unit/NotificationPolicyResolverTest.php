<?php

namespace Tests\Unit;

use App\Models\FaceMatchResult;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Contracts\NotificationStrategy;
use App\Services\Notification\Exceptions\NotificationStrategyNotFoundException;
use App\Services\Notification\NotificationPolicyResolver;
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

    public function test_it_rejects_an_unregistered_event_and_role_pair(): void
    {
        $this->expectException(NotificationStrategyNotFoundException::class);

        app(NotificationPolicyResolver::class)->resolve(
            $this->faceMatchConfirmedEvent(),
            new User(['role' => 'UNREGISTERED_ROLE'])
        );
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
