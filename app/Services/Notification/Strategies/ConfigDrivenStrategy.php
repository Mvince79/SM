<?php

namespace App\Services\Notification\Strategies;

use App\Models\Clue;
use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\KycVerification;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Services\Notification\Contracts\NotificationStrategy;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Render nội dung thông báo từ khai báo trong config/notification-policies.php.
 *
 * Tồn tại để khỏi phải viết vài chục strategy class chỉ để ghép chuỗi. Các loại
 * cần logic thật (vd FACE_MATCH_CONFIRMED) vẫn dùng class riêng — resolver ưu
 * tiên class trước, rồi mới tới khai báo config.
 *
 * Nội dung được nhận qua setter (không qua constructor) vì resolver lấy instance
 * từ container; xem NotificationPolicyResolver.
 */
class ConfigDrivenStrategy implements NotificationStrategy
{
    /** @var array<string, mixed> */
    private array $content = [];

    /**
     * @param  array<string, mixed>  $content
     */
    public function withContent(array $content): self
    {
        $clone = clone $this;
        $clone->content = $content;

        return $clone;
    }

    public function render(NotificationEvent $event, User $recipient): array
    {
        $payload = $this->placeholders($event);

        return [
            'title' => $this->interpolate((string) ($this->content['title'] ?? 'Bạn có thông báo mới'), $payload),
            'body' => $this->interpolate((string) ($this->content['body'] ?? ''), $payload),
            'action_url' => $this->resolveUrl($event, $this->content['url'] ?? null),
        ];
    }

    /**
     * Payload của event, cộng thêm vài khoá suy ra từ subject để khai báo config
     * không phải phụ thuộc hoàn toàn vào nơi phát event nhớ truyền đủ.
     *
     * @return array<string, scalar|null>
     */
    private function placeholders(NotificationEvent $event): array
    {
        $payload = array_filter(
            $event->payload ?? [],
            static fn (mixed $value): bool => is_scalar($value) || $value === null,
        );

        $subject = $event->subject;

        if ($subject instanceof MissingChild) {
            $payload['case_code'] ??= $subject->case_code;
            $payload['full_name'] ??= $subject->full_name;
        }

        if ($subject instanceof FaceMatchResult) {
            $subject->loadMissing('missingChild');
            $payload['case_code'] ??= $subject->missingChild?->case_code;
            $payload['full_name'] ??= $subject->missingChild?->full_name;
            $payload['score_percent'] ??= number_format((float) $subject->score * 100, 1);
            $payload['confidence_label'] ??= $subject->confidence_label;
        }

        if ($subject instanceof Clue) {
            $subject->loadMissing('missingChild');
            $payload['case_code'] ??= $subject->missingChild?->case_code;
            $payload['full_name'] ??= $subject->missingChild?->full_name;
        }

        return $payload;
    }

    /**
     * @param  array<string, scalar|null>  $payload
     */
    private function interpolate(string $template, array $payload): string
    {
        if ($template === '') {
            return '';
        }

        // Khoá dài thay trước để :case_code không bị :case ăn mất phần đuôi.
        $keys = array_keys($payload);
        usort($keys, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $replacements = [];

        foreach ($keys as $key) {
            $replacements[':' . $key] = (string) ($payload[$key] ?? '');
        }

        $rendered = strtr($template, $replacements);

        // Placeholder không có dữ liệu → bỏ hẳn, không để lộ ":reason" ra mặt người dùng.
        $rendered = preg_replace('/:[a-z_][a-z0-9_]*/i', '', $rendered) ?? $rendered;

        return trim(preg_replace('/\s{2,}/', ' ', $rendered) ?? $rendered);
    }

    private function resolveUrl(NotificationEvent $event, ?string $target): ?string
    {
        $subject = $event->subject;

        $missingId = match (true) {
            $subject instanceof MissingChild => $subject->getKey(),
            $subject instanceof FaceMatchResult => $subject->missing_id,
            $subject instanceof Clue => $subject->missing_id,
            default => null,
        };

        return match ($target) {
            'case' => $missingId ? route('missing.detail', ['id' => $missingId]) : null,
            'found' => $this->foundUrl($subject),
            'admin_case' => $this->adminCaseUrl($missingId),
            'support' => RouteFacade::has('support.index') ? route('support.index') : null,
            'kyc' => $subject instanceof KycVerification
                ? route('filament.admin.resources.kyc-verifications.view', ['record' => $subject->getKey()])
                : null,
            default => null,
        };
    }

    private function foundUrl(mixed $subject): ?string
    {
        if ($subject instanceof FoundChild) {
            return route('found-children.public-show', ['foundChild' => $subject->getKey()]);
        }

        if ($subject instanceof FaceMatchResult && $subject->found_id) {
            return route('found-children.public-show', ['foundChild' => $subject->found_id]);
        }

        return null;
    }

    /**
     * Route panel quản trị chỉ tồn tại khi Filament đã đăng ký; bọc lại để lệnh
     * CLI / test chạy ngoài context panel không bị nổ.
     */
    private function adminCaseUrl(?int $missingId): ?string
    {
        if (! $missingId || ! RouteFacade::has('filament.admin.resources.missing-children.view')) {
            return null;
        }

        return route('filament.admin.resources.missing-children.view', ['record' => $missingId]);
    }
}
