<?php

namespace Tests\Feature;

use App\Filament\Admin\Support\ActivityDiffBuilder;
use App\Filament\Admin\Support\Labels;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Hồi quy cho D-05:
 *  - Lỗi 1: resolveValue('user_id', ...) từng crash 500 do thiếu "use App\Models\User;"
 *    (PHP hiểu nhầm thành App\Filament\Admin\Support\User). Test gọi thẳng nhánh
 *    user_id để đảm bảo User::find phân giải đúng namespace.
 *  - Lỗi 2: cột "Mô tả" hiển thị token sự kiện (created/updated/deleted) tiếng Anh.
 *    Labels::event phải dịch token đã biết, nhưng GIỮ NGUYÊN mô tả tự do (tiếng Anh).
 */
class ActivityLabelsTest extends TestCase
{
    use RefreshDatabase;

    private function callResolveValue(string $key, mixed $raw): mixed
    {
        $method = new ReflectionMethod(ActivityDiffBuilder::class, 'resolveValue');
        $method->setAccessible(true);

        return $method->invoke(null, $key, $raw);
    }

    public function test_resolve_value_user_id_returns_real_full_name(): void
    {
        $user = User::factory()->create(['full_name' => 'Nguyen Van A']);

        $result = $this->callResolveValue('user_id', (string) $user->id);

        $this->assertSame('Nguyen Van A', $result);
    }

    public function test_resolve_value_user_id_falls_back_to_raw_when_missing(): void
    {
        $result = $this->callResolveValue('user_id', '999999');

        $this->assertSame('999999', $result);
    }

    public function test_event_labels_translate_known_tokens_and_keep_free_text(): void
    {
        $this->assertSame('Tạo mới', Labels::event('created'));
        $this->assertSame('Cập nhật', Labels::event('updated'));
        $this->assertSame('Xóa', Labels::event('deleted'));
        $this->assertSame('Khôi phục', Labels::event('restored'));

        // Mô tả tự do (dev log bằng activity()->log('...')) phải được giữ nguyên.
        $this->assertSame('Original photo accessed', Labels::event('Original photo accessed'));
        $this->assertSame('role_changed', Labels::event('role_changed'));

        $this->assertSame('—', Labels::event(null));
    }
}
