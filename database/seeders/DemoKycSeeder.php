<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoKycSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Demo@123456';

    public const CMND_EMAIL = 'demo.kyc.cmnd@timtre.vn';

    public const CCCD_EMAIL = 'demo.kyc.cccd@timtre.vn';

    /**
     * @return array<string, array{
     *     email: string,
     *     full_name: string,
     *     phone: string,
     *     id_type: string,
     *     id_number: string,
     *     dob: string
     * }>
     */
    public static function profiles(): array
    {
        return [
            'cmnd' => [
                'email' => self::CMND_EMAIL,
                'full_name' => 'Nguyễn Văn Demo',
                'phone' => '0906000006',
                'id_type' => 'CMND_9',
                'id_number' => '012345678',
                'dob' => '1992-04-15',
            ],
            'cccd' => [
                'email' => self::CCCD_EMAIL,
                'full_name' => 'Trần Thị Demo',
                'phone' => '0906000007',
                'id_type' => 'CCCD_12',
                'id_number' => '079092000001',
                'dob' => '1992-09-20',
            ],
        ];
    }

    public static function privateAssetPath(string $profile, string $side): string
    {
        return "demo-kyc/{$profile}/{$side}.jpg";
    }

    public function run(): void
    {
        $profiles = self::profiles();

        foreach (array_keys($profiles) as $profile) {
            foreach (['front', 'back', 'selfie'] as $side) {
                $source = $this->sourceAssetPath($profile, $side);

                if (! File::isFile($source)) {
                    throw new RuntimeException("Thiếu ảnh demo O-06: {$source}");
                }

                $stored = Storage::disk('private')->put(
                    self::privateAssetPath($profile, $side),
                    File::get($source),
                );

                if (! $stored) {
                    throw new RuntimeException(
                        'Không thể sao chép ảnh demo O-06 vào private storage.'
                    );
                }
            }
        }

        DB::transaction(function () use ($profiles): void {
            foreach ($profiles as $profile) {
                $user = User::withoutEvents(
                    fn (): User => User::query()->updateOrCreate(
                        ['email' => $profile['email']],
                        [
                            'full_name' => $profile['full_name'],
                            'email_verified_at' => now(),
                            'phone' => $profile['phone'],
                            'password' => Hash::make(self::DEMO_PASSWORD),
                            'role' => 'USER',
                            'status' => 'ACTIVE',
                            'kyc_status' => 'NONE',
                        ],
                    )
                );

                $user->kycVerification()->delete();
            }
        });

        $this->command?->info('Đã chuẩn bị xong kịch bản O-06 — Demo KYC CMND & CCCD.');
        $this->command?->line('CMND: '.self::CMND_EMAIL.' / '.self::DEMO_PASSWORD);
        $this->command?->line('CCCD: '.self::CCCD_EMAIL.' / '.self::DEMO_PASSWORD);
        $this->command?->line('Ảnh mẫu: storage/demo-assets/kyc-samples/');
    }

    private function sourceAssetPath(string $profile, string $side): string
    {
        return base_path("storage/demo-assets/kyc-samples/{$profile}/{$side}.jpg");
    }
}
