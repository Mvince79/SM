<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\MissingChildren\Pages\ViewMissingChild;
use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression test cho các HEADER ACTION chuyển trạng thái trên trang
 * ViewMissingChild (Duyệt / Từ chối / Đã tìm thấy / Đóng hồ sơ).
 *
 * LỖI ĐÃ SỬA: MissingChild::$guarded = ['status','approved_at','approved_by'],
 * nên $record->update(['status' => ...]) gọi fill() và BỎ QUA status (guard),
 * khiến nút báo thành công nhưng status đứng yên. Các action này được đổi sang
 * forceFill([...])->save() (đồng bộ với confirmHandoverByAdmin /
 * confirmReunionByAdmin). Nếu quay lại update(), 4 test dưới sẽ FAIL.
 *
 * Filament v5.7.6 + Livewire v4.3: test header action qua Livewire::test()
 * mountAction() + (setActionData()) + callMountedAction().
 */
class ViewMissingChildStatusActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'ADMIN']);
    }

    private function pendingCase(User $owner): MissingChild
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
            'status' => MissingChild::STATUS_PENDING,
        ]));
    }

    private function approvedCase(User $owner): MissingChild
    {
        $case = $this->pendingCase($owner);
        $case->forceFill(['status' => MissingChild::STATUS_APPROVED])->save();

        return $case->fresh();
    }

    // ----------------------------------------------------------------- Duyệt

    public function test_admin_approve_changes_status_to_approved(): void
    {
        $admin = $this->admin();
        $case = $this->pendingCase(User::factory()->create());

        Livewire::actingAs($admin)
            ->test(ViewMissingChild::class, ['record' => $case->getKey()])
            ->mountAction('approve')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $case->refresh();
        $this->assertSame(MissingChild::STATUS_APPROVED, $case->status);
        $this->assertNotNull($case->approved_at);
        $this->assertSame($admin->getKey(), $case->approved_by);
    }

    // --------------------------------------------------------------- Từ chối

    public function test_admin_reject_changes_status_to_rejected(): void
    {
        $admin = $this->admin();
        $case = $this->pendingCase(User::factory()->create());

        Livewire::actingAs($admin)
            ->test(ViewMissingChild::class, ['record' => $case->getKey()])
            ->mountAction('reject')
            ->setActionData(['rejection_reason' => 'Không đủ minh chứng, từ chối tạm thời.'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $case->refresh();
        $this->assertSame(MissingChild::STATUS_REJECTED, $case->status);
        $this->assertSame('Không đủ minh chứng, từ chối tạm thời.', $case->rejection_reason);
    }

    // ----------------------------------------------------------- Đã tìm thấy

    public function test_admin_mark_found_changes_status_to_found(): void
    {
        $admin = $this->admin();
        $case = $this->approvedCase(User::factory()->create());

        Livewire::actingAs($admin)
            ->test(ViewMissingChild::class, ['record' => $case->getKey()])
            ->mountAction('mark_found')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $case->refresh();
        $this->assertSame(MissingChild::STATUS_FOUND, $case->status);
        $this->assertNotNull($case->found_at);
    }

    // ----------------------------------------------------------------- Đóng

    public function test_admin_close_changes_status_to_closed(): void
    {
        $admin = $this->admin();
        $case = $this->approvedCase(User::factory()->create());

        Livewire::actingAs($admin)
            ->test(ViewMissingChild::class, ['record' => $case->getKey()])
            ->mountAction('close')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $case->refresh();
        $this->assertSame(MissingChild::STATUS_CLOSED, $case->status);
    }
}
