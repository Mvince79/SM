<?php

namespace App\Services\Notification;

use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Cửa vào duy nhất để phát thông báo trong toàn bộ hệ thống.
 *
 * Thay vì mỗi nút bấm / cron / AI tự gọi Mail::to() hay $user->notify() (từng
 * đó sinh ra 2 hệ thông báo song song, mỗi nơi một kiểu), mọi luồng đều gọi
 * Notifier::send(). Điều này bảo đảm: một đường ống, một bảng, một nơi sửa,
 * và escalation phủ được mọi loại event.
 *
 * Event được firstOrCreate theo (event_type, subject) để idempotent: chạy lại
 * nhiều lần (vd job retry, cron) không tạo bản ghi recipient trùng — DeliveryService
 * cũng có chốt firstOrNew để an toàn kép.
 */
class Notifier
{
    public function __construct(
        private readonly NotificationDeliveryService $deliveryService,
    ) {}

    /**
     * @param  iterable<User>  $recipients
     * @param  array<string, mixed>  $payload
     */
    public function send(
        string $eventType,
        Model $subject,
        iterable $recipients,
        array $payload = [],
        ?int $triggeredBy = null,
    ): ?NotificationEvent {
        $event = NotificationEvent::query()->firstOrCreate([
            'event_type' => $eventType,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ], [
            'payload' => $payload,
            'triggered_by_user_id' => $triggeredBy,
            'occurred_at' => now(),
        ]);

        // firstOrCreate có thể trả bản ghi cũ (idempotent) — nếu event này sinh
        // lại chỉ để nhắc lại (vd nhắc đoàn tụ từng mốc), caller nên truyền
        // subject khác (vd một log row) hoặc tự quản lý. Ở đây ta vẫn deliver
        // vào cùng event: recipient đã tồn tại sẽ được bỏ qua (đúng cho
        // một-shot). Với lặp (reminder), xem RemindFamilyReunionConfirmation
        // — nó tạo event riêng mang payload['milestone'].
        $event->setRelation('subject', $subject);

        try {
            $this->deliveryService->deliver($event, $recipients);
        } catch (\Throwable $e) {
            Log::error('Gửi thông báo thất bại', [
                'event_type' => $eventType,
                'subject' => $subject->getMorphClass() . '#' . $subject->getKey(),
                'error' => $e->getMessage(),
            ]);
        }

        return $event;
    }
}
