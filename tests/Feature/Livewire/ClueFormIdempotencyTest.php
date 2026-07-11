<?php

namespace Tests\Feature\Livewire;

use App\Events\NewClueSubmitted;
use App\Http\Livewire\MissingChild\ClueForm;
use App\Models\Clue;
use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use App\Notifications\ClueSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ClueFormIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function makeMissingChild(): MissingChild
    {
        $owner = User::factory()->create();
        $province = Province::factory()->create();

        return MissingChild::create([
            'user_id' => $owner->id,
            'full_name' => 'Bé Nguyễn Văn A',
            'gender' => 'MALE',
            'province_id' => $province->id,
            'missing_date' => now()->toDateString(),
            'contact_name' => 'Nguyễn Văn B',
            'contact_phone' => '0905123456',
            'status' => 'APPROVED',
        ]);
    }

    public function test_double_submit_same_idempotency_key_creates_only_one_clue(): void
    {
        Notification::fake();
        Event::fake([NewClueSubmitted::class]);

        $missing = $this->makeMissingChild();
        $reporter = User::factory()->create();

        $component = Livewire::actingAs($reporter)
            ->test(ClueForm::class, ['missingId' => $missing->id])
            ->set('description', 'Tôi thấy bé mặc áo xanh gần chợ Bình Tây lúc 14h.');

        $key = $component->get('idempotencyKey');
        $this->assertNotEmpty($key);

        // Gửi lần 1 (submit thật)
        $component->call('submit');
        $this->assertSame(1, Clue::count());

        // Giả lập double-click nhanh: gửi lại với CÙNG idempotencyKey + cùng nội dung
        $component
            ->set('idempotencyKey', $key)
            ->set('description', 'Tôi thấy bé mặc áo xanh gần chợ Bình Tây lúc 14h.')
            ->call('submit');

        $this->assertSame(1, Clue::count(), 'Không được tạo trùng Clue khi submit 2 lần cùng idempotency key');

        // Notification + Event chỉ được bắn đúng 1 lần, không trùng
        Notification::assertSentTimes(ClueSubmittedNotification::class, 1);
        Event::assertDispatchedTimes(NewClueSubmitted::class, 1);
    }

    public function test_different_idempotency_key_creates_separate_clues(): void
    {
        Notification::fake();
        Event::fake([NewClueSubmitted::class]);

        $missing = $this->makeMissingChild();
        $reporter = User::factory()->create();

        Livewire::actingAs($reporter)
            ->test(ClueForm::class, ['missingId' => $missing->id])
            ->set('description', 'Manh mối thứ nhất.')
            ->call('submit');

        Livewire::actingAs($reporter)
            ->test(ClueForm::class, ['missingId' => $missing->id])
            ->set('description', 'Manh mối thứ hai.')
            ->call('submit');

        $this->assertSame(2, Clue::count(), 'Idempotency key khác nhau -> phải tạo 2 Clue riêng biệt');
        Notification::assertSentTimes(ClueSubmittedNotification::class, 2);
        Event::assertDispatchedTimes(NewClueSubmitted::class, 2);
    }

    public function test_guest_cannot_submit_without_login(): void
    {
        $missing = $this->makeMissingChild();

        Livewire::test(ClueForm::class, ['missingId' => $missing->id])
            ->set('description', 'Manh mối ẩn danh không đăng nhập.')
            ->call('submit');

        $this->assertSame(0, Clue::count());
    }
}