<?php

namespace Tests\Feature\Livewire;

use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateMissingFormIdempotencyTest extends TestCase
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
            'agreedFalseReportLiability' => true,
        ];
    }

    public function test_double_submit_same_idempotency_key_creates_only_one_record(): void
    {
        $province = Province::factory()->create(['is_active' => 1]);
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);

        $component = Livewire::actingAs($user)
            ->test('missing.create-missing-form')
            ->set('province_id', (string) $province->id)
            ->set($this->baseFormData());

        $key = $component->get('idempotencyKey');
        $this->assertNotEmpty($key);

        // Gửi lần 1
        $component->call('submit');
        $component->assertHasNoErrors();
        $this->assertSame(1, MissingChild::count());

        // Gửi lần 2 với CÙNG idempotencyKey (giả lập double-click)
        $component->set('idempotencyKey', $key)->call('submit');

        $this->assertSame(1, MissingChild::count(), 'Không được tạo trùng khi submit 2 lần cùng idempotency key');
    }

    public function test_different_idempotency_key_creates_separate_records(): void
    {
        $province = Province::factory()->create(['is_active' => 1]);
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);

        Livewire::actingAs($user)
            ->test('missing.create-missing-form')
            ->set('province_id', (string) $province->id)
            ->set($this->baseFormData())
            ->call('submit');

        Livewire::actingAs($user)
            ->test('missing.create-missing-form')
            ->set('province_id', (string) $province->id)
            ->set($this->baseFormData())
            ->call('submit');

        $this->assertSame(2, MissingChild::count(), 'Idempotency key khác nhau -> phải tạo 2 record riêng biệt');
    }
}