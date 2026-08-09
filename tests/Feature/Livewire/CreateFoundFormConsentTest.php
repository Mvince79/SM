<?php

namespace Tests\Feature\Livewire;

use App\Models\FoundChild;
use App\Models\Province;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateFoundFormConsentTest extends TestCase
{
    use RefreshDatabase;

    private function baseFormData(): array
    {
        return [
            'province_id' => '',
            'found_date' => now()->toDateString(),
            'gender_estimate' => 'UNKNOWN',
            'special_marks' => str_repeat('a', 15),
            'agreed' => true,
            'agreedDataPolicy' => true,
        ];
    }

    public function test_cannot_submit_without_checking_both_consent_boxes(): void
    {
        $province = Province::factory()->create(['is_active' => 1]);
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);

        Livewire::actingAs($user)
            ->test('found-children.create-found-form')
            ->set(array_merge($this->baseFormData(), ['province_id' => (string) $province->id]))
            ->set('agreedDataPolicy', false)
            ->call('submit')
            ->assertHasErrors(['agreedDataPolicy' => 'accepted']);

        $this->assertSame(0, FoundChild::count());
        $this->assertSame(0, UserConsent::where('user_id', $user->id)->count());
    }

    public function test_checking_both_boxes_saves_all_consent_records_on_submit(): void
    {
        $province = Province::factory()->create(['is_active' => 1]);
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);

        Livewire::actingAs($user)
            ->test('found-children.create-found-form')
            ->set(array_merge($this->baseFormData(), ['province_id' => (string) $province->id]))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, FoundChild::count());

        $this->assertSame(
            3,
            UserConsent::where('user_id', $user->id)->count(),
            'Phải có đủ 3 bản ghi consent riêng biệt: TOS, PRIVACY_POLICY, CHILD_DATA_POLICY.',
        );

        foreach (['TOS', 'PRIVACY_POLICY', 'CHILD_DATA_POLICY'] as $type) {
            $record = UserConsent::where('user_id', $user->id)
                ->where('consent_type', $type)
                ->first();

            $this->assertNotNull($record, "Phải có bản ghi consent {$type} sau khi submit thành công.");
            $this->assertSame(
                config("legal.versions.$type"),
                $record->version,
                "Bản ghi consent {$type} phải lưu đúng version hiện hành trong config/legal.php.",
            );
            $this->assertNotNull($record->accepted_at);
        }
    }
}