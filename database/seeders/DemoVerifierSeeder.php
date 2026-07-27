<?php

namespace Database\Seeders;

use App\Models\HandoverPoint;
use App\Models\ThirdPartyVerifierProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoVerifierSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereIn('role', ['ADMIN', 'MODERATOR'])->first();

        if (! $admin) {
            throw new RuntimeException(
                'Không tìm thấy ADMIN hoặc MODERATOR để xác minh tài khoản demo.'
            );
        }

        $policePoint = HandoverPoint::query()
            ->where('name', 'Công an phường Bến Nghé')
            ->where('type', 'POLICE_STATION')
            ->first();

        $ngoPoint = HandoverPoint::query()
            ->where('name', 'Làng trẻ em SOS Gò Vấp')
            ->where('type', 'NGO')
            ->first();

        if (! $policePoint || ! $ngoPoint) {
            throw new RuntimeException(
                'Không tìm thấy điểm bàn giao demo. Hãy chạy HandoverPointsSeeder trước DemoVerifierSeeder.'
            );
        }

        $policeVerifier = User::updateOrCreate(
            ['email' => 'police.verifier@missingchild.vn'],
            [
                'full_name' => 'Demo Police Verifier',
                'phone' => '+84909000111',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role' => 'THIRD_PARTY_VERIFIER',
                'kyc_status' => 'VERIFIED',
                'status' => 'ACTIVE',
                'province_id' => $policePoint->province_id,
            ]
        );

        ThirdPartyVerifierProfile::updateOrCreate(
            ['user_id' => $policeVerifier->id],
            [
                'organization_type' => 'POLICE',
                'organization_name' => $policePoint->responsible_org_name
                    ?: $policePoint->name,
                'badge_or_staff_id' => 'POLICE-DEMO-001',
                'handover_point_id' => $policePoint->id,
                'verified_by_admin_id' => $admin->id,
                'verified_at' => now(),
            ]
        );

        $ngoVerifier = User::updateOrCreate(
            ['email' => 'ngo.verifier@missingchild.vn'],
            [
                'full_name' => 'Demo NGO Verifier',
                'phone' => '+84909000222',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role' => 'THIRD_PARTY_VERIFIER',
                'kyc_status' => 'VERIFIED',
                'status' => 'ACTIVE',
                'province_id' => $ngoPoint->province_id,
            ]
        );

        ThirdPartyVerifierProfile::updateOrCreate(
            ['user_id' => $ngoVerifier->id],
            [
                'organization_type' => 'NGO',
                'organization_name' => $ngoPoint->responsible_org_name
                    ?: $ngoPoint->name,
                'badge_or_staff_id' => 'NGO-DEMO-001',
                'handover_point_id' => $ngoPoint->id,
                'verified_by_admin_id' => $admin->id,
                'verified_at' => now(),
            ]
        );

        $this->command?->info('Đã tạo 2 tài khoản Third-Party Verifier demo.');
        $this->command?->line('Police: police.verifier@missingchild.vn / password123');
        $this->command?->line('NGO: ngo.verifier@missingchild.vn / password123');
    }
}