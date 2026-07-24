<?php

namespace Database\Seeders;

use App\Models\EscalationQueue;
use App\Models\FaceMatchResult;
use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\NotificationEvent;
use App\Models\NotificationRecipient;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoEscalationSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'demo.escalation.admin@timtre.vn';

    public const FAMILY_EMAIL = 'demo.escalation.family@timtre.vn';

    public const DEMO_PASSWORD = 'Demo@123456';

    public const CASE_CODE = 'MC-DEMO-ESCALATION';

    public const CASE_SLUG = 'nguyen-minh-an-mc-demo-escalation';

    public function run(): void
    {
        $escalation = DB::transaction(function (): EscalationQueue {
            $thresholdMinutes = max(
                1,
                (int) config('notification.escalation_threshold_minutes', 30)
            );
            $occurredAt = now()->subMinutes($thresholdMinutes + 5);

            $province = Province::query()->updateOrCreate(
                ['code' => 'VN-DMO'],
                [
                    'name' => 'Tỉnh Demo Escalation',
                    'short_code' => 'DMO',
                    'region' => 'NAM',
                    'is_active' => true,
                ],
            );

            $admin = User::withoutEvents(fn (): User => User::query()->updateOrCreate(
                ['email' => self::ADMIN_EMAIL],
                [
                    'full_name' => 'Admin Demo Escalation',
                    'email_verified_at' => now(),
                    'phone' => '0905000111',
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'role' => 'ADMIN',
                    'status' => 'ACTIVE',
                    'province_id' => $province->getKey(),
                ],
            ));

            $family = User::withoutEvents(fn (): User => User::query()->updateOrCreate(
                ['email' => self::FAMILY_EMAIL],
                [
                    'full_name' => 'Gia đình Demo Escalation',
                    'email_verified_at' => now(),
                    'phone' => '0905123456',
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'role' => 'USER',
                    'status' => 'ACTIVE',
                    'province_id' => $province->getKey(),
                ],
            ));

            $missingChild = MissingChild::withoutEvents(
                fn (): MissingChild => MissingChild::query()->updateOrCreate(
                    ['case_code' => self::CASE_CODE],
                    [
                        'user_id' => $family->getKey(),
                        'full_name' => 'Nguyễn Minh An',
                        'slug' => self::CASE_SLUG,
                        'age_estimate' => 8,
                        'gender' => 'MALE',
                        'province_id' => $province->getKey(),
                        'district' => 'Quận Demo',
                        'address_detail' => 'Khu vực phục vụ kịch bản O-03',
                        'missing_date' => now()->subDays(2)->toDateString(),
                        'contact_name' => $family->full_name,
                        'contact_phone' => $family->phone,
                        'contact_relation' => 'PARENT',
                        'status' => 'APPROVED',
                        'approved_at' => now()->subDays(2),
                        'approved_by' => $admin->getKey(),
                    ],
                )
            );

            $foundChild = FoundChild::withoutEvents(
                fn (): FoundChild => FoundChild::query()->updateOrCreate(
                    [
                        'user_id' => $admin->getKey(),
                        'current_location' => 'Công an phường Demo',
                    ],
                    [
                        'province_id' => $province->getKey(),
                        'found_date' => now()->subDay()->toDateString(),
                        'district' => 'Quận Demo',
                        'address_detail' => 'Điểm phát hiện dùng cho kịch bản O-03',
                        'gender_estimate' => 'MALE',
                        'age_estimate' => 8,
                        'condition_desc' => 'Sức khỏe ổn định',
                        'status' => 'MATCHED',
                        'approved_at' => now()->subDay(),
                        'approved_by' => $admin->getKey(),
                    ],
                )
            );

            $match = FaceMatchResult::withoutEvents(
                fn (): FaceMatchResult => FaceMatchResult::query()->updateOrCreate(
                    [
                        'missing_id' => $missingChild->getKey(),
                        'found_id' => $foundChild->getKey(),
                        'source' => 'MANUAL',
                    ],
                    [
                        'score' => 0.9345,
                        'is_matched' => true,
                        'confidence_level' => 'HIGH',
                        'model_used' => 'Demo fixture',
                        'status' => 'CONFIRMED',
                        'confirmed_by' => $admin->getKey(),
                        'confirmed_at' => $occurredAt,
                        'notified_at' => $occurredAt,
                    ],
                )
            );

            $event = NotificationEvent::query()->updateOrCreate(
                [
                    'event_type' => NotificationEvent::TYPE_FACE_MATCH_CONFIRMED,
                    'subject_type' => $match->getMorphClass(),
                    'subject_id' => $match->getKey(),
                ],
                [
                    'payload' => [
                        'demo_task' => 'O-03',
                        'missing_id' => $missingChild->getKey(),
                        'found_id' => $foundChild->getKey(),
                        'score' => $match->score,
                    ],
                    'triggered_by_user_id' => $admin->getKey(),
                    'occurred_at' => $occurredAt,
                ],
            );

            $recipient = NotificationRecipient::query()->updateOrCreate(
                [
                    'notification_event_id' => $event->getKey(),
                    'user_id' => $family->getKey(),
                ],
                [
                    'role_at_time' => 'USER',
                    'rendered_title' => '[DEMO O-03] Kết quả trùng khớp chưa được đọc',
                    'rendered_body' => 'Thông báo này được giữ ở trạng thái chưa đọc để minh họa cơ chế hỗ trợ khẩn cấp.',
                    'action_url' => route('missing.detail', ['id' => $missingChild->getKey()]),
                    'read_at' => null,
                ],
            );

            $recipient->escalationQueue()->delete();

            $exitCode = Artisan::call('notifications:scan-unread-critical');

            if ($exitCode !== 0) {
                throw new RuntimeException(
                    "Command notifications:scan-unread-critical thất bại với mã {$exitCode}."
                );
            }

            return $recipient->escalationQueue()
                ->where('status', EscalationQueue::STATUS_PENDING)
                ->first()
                ?? throw new RuntimeException(
                    'Không tạo được escalation PENDING. Kiểm tra cấu hình notification.critical_event_types.'
                );
        });

        $this->command?->info('Đã chuẩn bị xong kịch bản O-03 — Demo Escalation.');
        $this->command?->line("Escalation queue ID: {$escalation->getKey()}");
        $this->command?->line('Admin: '.self::ADMIN_EMAIL.' / '.self::DEMO_PASSWORD);
        $this->command?->line('Gia đình: '.self::FAMILY_EMAIL.' / '.self::DEMO_PASSWORD);
    }
}
