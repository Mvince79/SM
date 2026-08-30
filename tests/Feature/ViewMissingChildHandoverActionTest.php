<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\MissingChildren\Pages\ViewMissingChild;
use App\Models\AuditLog;
use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * H-01/H-04 — Test action "mark_handed_over" trên trang ViewRecord
 * (ViewMissingChild). Đây là HEADER ACTION (mount action), KHÁC với action
 * cùng tên trong MissingChildrenTable.php đã có test riêng.
 *
 * Filament v5.7.6 + Livewire v4.3: test page/header action qua Livewire::test()
 * kết hợp mountAction() + setActionData() + callMountedAction(). Action có
 * form (reason + handover_point_id) nên Livewire::test dựng được schema form
 * và validate giống UI thật.
 *
 * Phủ:
 *  1. Admin nhập lý do RỖNG → bị chặn (form ValidationException), status giữ
 *     FOUND, field 'reason' có lỗi.
 *  2. Admin nhập lý do NGẮN (< 20 ký tự) → bị chặn, status giữ FOUND, lỗi
 *     trên field 'reason'.
 *  3. Admin nhập lý do ĐỦ (>= 20 ký tự) → thành công, status -> HANDED_OVER,
 *     AuditLog ghi actor_role = ADMIN, reason lưu đúng.
 */
class ViewMissingChildHandoverActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Trang ViewMissingChild là Livewire component thuộc panel 'admin'.
        // Livewire::test() bỏ qua middleware SetUpPanel nên ta set panel thủ
        // công. AdminPanelProvider đánh dấu panel này là ->default() nên về lý
        // thuyết có thể bỏ qua, nhưng set tường minh để test không phụ thuộc
        // vào thứ tự chạy / panel khác.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'ADMIN']);
    }

    private function foundCase(User $owner): MissingChild
    {
        $province = Province::query()->first() ?? Province::factory()->create();

        return MissingChild::withoutEvents(fn () => MissingChild::query()->forceCreate([
            'user_id' => $owner->getKey(),
            'case_code' => 'MC-' . fake()->unique()->numerify('######'),
            'full_name' => fake()->name(),
            'gender' => 'UNKNOWN',
            'province_id' => $province->getKey(),
            'missing_date' => now()->subMonth(),
            'contact_name' => 'Người thân',
            'contact_phone' => '0905123456',
            'contact_relation' => 'PARENT',
            'status' => MissingChild::STATUS_FOUND,
            'found_at' => now()->subDays(2),
        ]));
    }

    // ----------------------------------------------------------------- 1

    public function test_admin_mark_handed_over_rejects_empty_reason(): void
    {
        $admin = $this->admin();
        $case = $this->foundCase(User::factory()->create());

        Livewire::actingAs($admin)
            ->test(ViewMissingChild::class, ['record' => $case->getKey()])
            ->mountAction('mark_handed_over')
            ->setActionData(['reason' => '', 'handover_point_id' => null])
            ->callMountedAction()
            ->assertHasActionErrors(['reason']);

        // Bị chặn ở lớp validate form → model/observer KHÔNG chạy → status giữ FOUND.
        $this->assertSame(MissingChild::STATUS_FOUND, $case->fresh()->status);
    }

    // ----------------------------------------------------------------- 2

    public function test_admin_mark_handed_over_rejects_short_reason(): void
    {
        $admin = $this->admin();
        $case = $this->foundCase(User::factory()->create());

        Livewire::actingAs($admin)
            ->test(ViewMissingChild::class, ['record' => $case->getKey()])
            ->mountAction('mark_handed_over')
            ->setActionData(['reason' => 'Quá ngắn', 'handover_point_id' => null])
            ->callMountedAction()
            ->assertHasActionErrors(['reason']);

        $this->assertSame(MissingChild::STATUS_FOUND, $case->fresh()->status);
    }

    // ----------------------------------------------------------------- 3

    public function test_admin_mark_handed_over_succeeds_with_valid_reason(): void
    {
        $admin = $this->admin();
        $case = $this->foundCase(User::factory()->create());

        $reason = 'Đã gọi điện xác minh qua gia đình, công an phường X xác nhận đã bàn giao bé vào ngày hôm qua.';

        Livewire::actingAs($admin)
            ->test(ViewMissingChild::class, ['record' => $case->getKey()])
            ->mountAction('mark_handed_over')
            ->setActionData([
                'reason' => $reason,
                'handover_point_id' => null,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $case->refresh();
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $case->status);
        $this->assertSame($admin->getKey(), $case->handover_confirmed_by);
        $this->assertNotNull($case->handover_confirmed_at);

        // AuditLog: actor là ADMIN, reason lưu đúng, new_value.status = HANDED_OVER.
        $log = AuditLog::query()
            ->where('target_type', $case->getTable())
            ->where('target_id', $case->getKey())
            ->latest('created_at')
            ->first();

        $this->assertNotNull($log, 'Phải có 1 AuditLog sau khi admin bàn giao.');
        $this->assertSame('ADMIN', $log->actor_role);
        $this->assertSame($admin->getKey(), $log->actor_id);
        $this->assertSame($reason, $log->reason);
        $this->assertSame(MissingChild::STATUS_HANDED_OVER, $log->new_value['status']);
    }
}
