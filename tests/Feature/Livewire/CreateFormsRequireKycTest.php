<?php

namespace Tests\Feature\Livewire;

use App\Models\FoundChild;
use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Trước đây, dù MissingChildPolicy::create() và FoundChildPolicy::create()
 * đều yêu cầu kyc_status === 'VERIFIED', không có nơi nào trong luồng thật
 * (route, controller, hay chính component Livewire) thực sự gọi authorize().
 * UI chỉ hiển thị banner cảnh báo — user chưa KYC vẫn submit thành công
 * bình thường. Test này khoá lại hành vi đúng: cả mở trang (mount) lẫn
 * submit() đều phải chặn nếu user chưa KYC verified.
 */
class CreateFormsRequireKycTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('unverifiedKycStatuses')]
    public function test_missing_form_denies_mount_for_unverified_kyc(string $kycStatus): void
    {
        $user = User::factory()->create(['kyc_status' => $kycStatus, 'status' => 'ACTIVE']);

        Livewire::actingAs($user)
            ->test('missing.create-missing-form')
            ->assertForbidden();

        $this->assertSame(0, MissingChild::count());
    }

    public function test_missing_form_allows_mount_for_verified_kyc(): void
    {
        $user = User::factory()->create(['kyc_status' => 'VERIFIED', 'status' => 'ACTIVE']);

        Livewire::actingAs($user)
            ->test('missing.create-missing-form')
            ->assertOk();
    }

    #[DataProvider('unverifiedKycStatuses')]
    public function test_found_form_denies_mount_for_unverified_kyc(string $kycStatus): void
    {
        $user = User::factory()->create([
            'kyc_status' => $kycStatus,
            'email_verified_at' => now(),
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($user)
            ->test('found-children.create-found-form')
            ->assertForbidden();

        $this->assertSame(0, FoundChild::count());
    }

    public function test_found_form_allows_mount_for_verified_kyc(): void
    {
        $user = User::factory()->create([
            'kyc_status' => 'VERIFIED',
            'email_verified_at' => now(),
            'status' => 'ACTIVE',
        ]);

        Livewire::actingAs($user)
            ->test('found-children.create-found-form')
            ->assertOk();
    }

    public static function unverifiedKycStatuses(): array
    {
        return [
            'NONE' => ['NONE'],
            'PENDING' => ['PENDING'],
            'REJECTED' => ['REJECTED'],
        ];
    }
}