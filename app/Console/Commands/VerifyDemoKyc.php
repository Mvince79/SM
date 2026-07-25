<?php

namespace App\Console\Commands;

use App\Jobs\ProcessKycJob;
use App\Models\KycVerification;
use App\Models\User;
use App\Services\EkycService;
use Database\Seeders\DemoKycSeeder;
use Illuminate\Console\Command;
use Throwable;

class VerifyDemoKyc extends Command
{
    protected $signature = 'demo:verify-kyc
        {--prepare-only : Chỉ reset tài khoản và chép ảnh mẫu, chưa chạy OCR/face match}';

    protected $description = 'Chuẩn bị và kiểm tra trọn luồng demo KYC cho CMND 9 số và CCCD 12 số';

    public function handle(EkycService $ekycService): int
    {
        $seeder = app(DemoKycSeeder::class);
        $seeder->setContainer($this->laravel);
        $seeder->setCommand($this);
        $seeder->run();

        if ($this->option('prepare-only')) {
            $this->newLine();
            $this->info('Hai tài khoản đang ở trạng thái NONE, sẵn sàng demo bằng giao diện.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Bắt đầu chạy OCR và ArcFace đồng bộ để rehearsal O-06...');

        foreach (DemoKycSeeder::profiles() as $profileKey => $profile) {
            $result = $this->verifyProfile($profileKey, $profile, $ekycService);

            if ($result !== self::SUCCESS) {
                $this->newLine();
                $this->warn(
                    'Chạy lại "php artisan demo:verify-kyc --prepare-only" để reset dữ liệu trước khi demo UI.'
                );

                return $result;
            }
        }

        $this->newLine();
        $this->info('O-06 PASS: CMND và CCCD đều OCR đúng, khuôn mặt khớp và KYC VERIFIED.');
        $this->line(
            'Reset trước buổi demo UI: php artisan demo:verify-kyc --prepare-only'
        );

        return self::SUCCESS;
    }

    /**
     * @param  array{
     *     email: string,
     *     full_name: string,
     *     phone: string,
     *     id_type: string,
     *     id_number: string,
     *     dob: string
     * }  $profile
     */
    private function verifyProfile(
        string $profileKey,
        array $profile,
        EkycService $ekycService,
    ): int {
        $user = User::query()->where('email', $profile['email'])->firstOrFail();

        $kyc = KycVerification::query()->create([
            'user_id' => $user->getKey(),
            'id_type' => $profile['id_type'],
            'id_number' => $profile['id_number'],
            'full_name_on_id' => $profile['full_name'],
            'dob_on_id' => $profile['dob'],
            'front_image_path' => DemoKycSeeder::privateAssetPath($profileKey, 'front'),
            'back_image_path' => DemoKycSeeder::privateAssetPath($profileKey, 'back'),
            'selfie_path' => DemoKycSeeder::privateAssetPath($profileKey, 'selfie'),
            'status' => 'PENDING',
        ]);

        $user->forceFill(['kyc_status' => 'PENDING'])->save();

        try {
            (new ProcessKycJob($kyc->getKey()))->handle($ekycService);
        } catch (Throwable $exception) {
            report($exception);
            $this->error(
                "{$profile['id_type']} FAIL: {$exception->getMessage()}"
            );

            return self::FAILURE;
        }

        $kyc->refresh();
        $user->refresh();
        $minimumScore = (float) config('face_match.high_confidence_threshold', 0.85);

        if ($kyc->status !== 'VERIFIED'
            || $user->kyc_status !== 'VERIFIED'
            || $kyc->id_type !== $profile['id_type']
            || (float) $kyc->face_match_score < $minimumScore) {
            $this->error(
                sprintf(
                    '%s FAIL: status=%s, detected_type=%s, face_score=%.4f',
                    $profile['id_type'],
                    $kyc->status,
                    $kyc->id_type ?? 'NULL',
                    (float) $kyc->face_match_score,
                )
            );

            return self::FAILURE;
        }

        $this->line(
            sprintf(
                '%s PASS — %s — OCR: %s — Face score: %.4f',
                $profile['id_type'],
                $profile['email'],
                $kyc->ocr_provider,
                (float) $kyc->face_match_score,
            )
        );

        return self::SUCCESS;
    }
}
