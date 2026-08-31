<?php

namespace Tests\Feature\Livewire;

use App\Models\FoundChild;
use App\Models\Province;
use App\Models\User;
use App\Services\GeoBoundaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateFoundFormIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // V-02 — GeoBoundaryService phụ thuộc file GeoJSON tĩnh (có thể không có
        // trong môi trường test). Mock trả UNVERIFIED để test luồng form không
        // lệ thuộc dữ liệu ranh giới, không thay đổi logic production.
        $this->mock(GeoBoundaryService::class)
            ->shouldReceive('lookup')
            ->andReturn(['status' => 'UNVERIFIED', 'province' => null, 'ward' => null]);
    }

    public function test_double_submit_same_idempotency_key_creates_only_one_found_child(): void
    {
        $province = Province::factory()->create(['is_active' => 1]);
        $user = User::factory()->create(['kyc_status' => 'VERIFIED']);

        $component = Livewire::actingAs($user)
            ->test('found-children.create-found-form')
            ->set('province_id', (string) $province->id)
            ->set('found_date', now()->toDateString())
            ->set('special_marks', 'Ao xanh, co vet seo nho o tay')
            ->set('agreed', true)
            ->set('agreedDataPolicy', true)
            ->set('dataUseConsent', true)->set('lat', '21.0285')->set('lng', '105.8542');

        $key = $component->get('idempotencyKey');
        $this->assertNotEmpty($key);

        $component->call('submit');
        $this->assertSame(1, FoundChild::count());

        $component->set('idempotencyKey', $key)->call('submit');
        $this->assertSame(1, FoundChild::count());
    }
}