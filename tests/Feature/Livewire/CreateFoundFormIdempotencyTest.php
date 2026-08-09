<?php

namespace Tests\Feature\Livewire;

use App\Models\FoundChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateFoundFormIdempotencyTest extends TestCase
{
    use RefreshDatabase;

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
            ->set('agreedDataPolicy', true);

        $key = $component->get('idempotencyKey');
        $this->assertNotEmpty($key);

        $component->call('submit');
        $this->assertSame(1, FoundChild::count());

        $component->set('idempotencyKey', $key)->call('submit');
        $this->assertSame(1, FoundChild::count());
    }
}