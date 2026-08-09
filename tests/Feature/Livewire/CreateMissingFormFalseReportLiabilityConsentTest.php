<?php

namespace Tests\Feature\Livewire;

use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateMissingFormFalseReportLiabilityConsentTest extends TestCase
{
    use RefreshDatabase;

    private function baseFormData(): array
    {
        return [
            'full_name' => 'Nguyễn Văn An',
            'gender' => 'MALE',
            'missing_date' => now()->toDateString(),
            'special_marks' => str_repeat('a', 25),
            'contact_name' => 'Nguyễn Văn Bình',
            'contact_phone' => '0905123456',
            'contact_relation' => 'Mẹ',
            'agreed' => true,
            'agreedDataPolicy' => true,
        ];
    }

    public function test_cannot_submit_without_checking_false_report_liability_box(): void
    {
        $province = Province::factory()->create(['is_active' => 1]);
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);

        Livewire::actingAs($user)
            ->test('missing.create-missing-form')
            ->set('province_id', (string) $province->id)
            ->set($this->baseFormData())
            ->set('agreedFalseReportLiability', false)
            ->call('submit')
            ->assertHasErrors(['agreedFalseReportLiability' => 'accepted']);

        $this->assertSame(0, MissingChild::count());
        $this->assertSame(
            0,
            UserConsent::where('user_id', $user->id)
                ->where('consent_type', 'FALSE_REPORT_LIABILITY')
                ->count(),
        );
    }

    public function test_checking_the_box_saves_the_consent_record_on_submit(): void
    {
        $province = Province::factory()->create(['is_active' => 1]);
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);

        Livewire::actingAs($user)
            ->test('missing.create-missing-form')
            ->set('province_id', (string) $province->id)
            ->set($this->baseFormData())
            ->set('agreedFalseReportLiability', true)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, MissingChild::count());

        $consent = UserConsent::where('user_id', $user->id)
            ->where('consent_type', 'FALSE_REPORT_LIABILITY')
            ->first();

        $this->assertNotNull($consent, 'Phải có bản ghi consent FALSE_REPORT_LIABILITY sau khi submit thành công.');
        $this->assertSame(config('legal.versions.FALSE_REPORT_LIABILITY'), $consent->version);
        $this->assertNotNull($consent->accepted_at);

        $this->assertSame(
            4,
            UserConsent::where('user_id', $user->id)->count(),
            'Phải có đủ 4 bản ghi consent riêng biệt: TOS, PRIVACY_POLICY, CHILD_DATA_POLICY, FALSE_REPORT_LIABILITY.',
        );

        foreach (['TOS', 'PRIVACY_POLICY', 'CHILD_DATA_POLICY', 'FALSE_REPORT_LIABILITY'] as $type) {
            $record = UserConsent::where('user_id', $user->id)
                ->where('consent_type', $type)
                ->first();

            $this->assertNotNull($record, "Phải có bản ghi consent {$type} sau khi submit thành công.");
            $this->assertSame(
                config("legal.versions.$type"),
                $record->version,
                "Bản ghi consent {$type} phải lưu đúng version hiện hành trong config/legal.php.",
            );
        }
    }
}