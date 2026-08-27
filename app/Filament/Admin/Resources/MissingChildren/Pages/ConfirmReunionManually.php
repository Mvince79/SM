<?php

namespace App\Filament\Admin\Resources\MissingChildren\Pages;

use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
use App\Models\MissingChild;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * H-04 — Trang admin xác nhận đoàn tụ hộ gia đình.
 *
 * Dùng cho các hồ sơ đã ở trạng thái HANDED_OVER mà gia đình không tự xác nhận
 * được (admin đã gọi điện xác minh). Bắt buộc nhập lý do >= 20 ký tự; sau khi
 * lưu, StatusChangeAuditObserver ghi audit_logs với actor_role = ADMIN.
 *
 * Logic nghiệp vụ dùng chung với table action "Xác nhận đoàn tụ hộ"
 * (MissingChildrenTable) qua MissingChild::confirmReunionByAdmin().
 */
class ConfirmReunionManually extends Page
{
    use InteractsWithRecord;

    protected static string $resource = MissingChildResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $title = 'Xác nhận đoàn tụ hộ';

    protected static ?string $breadcrumb = 'Xác nhận đoàn tụ hộ';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->form->fill();
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        if (! $record->isHandedOver()) {
            return 'Hồ sơ này không ở trạng thái "Đã bàn giao" nên không thể xác nhận đoàn tụ hộ.';
        }

        return "Hồ sơ {$record->case_code} — {$record->full_name}: chỉ dùng khi đã liên hệ và xác minh được với gia đình.";
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('reason')
                    ->label('Lý do xác nhận (bắt buộc, tối thiểu 20 ký tự)')
                    ->required()
                    ->minLength(20)
                    ->rows(4)
                    ->helperText('Ghi rõ đã liên hệ gia đình qua kênh nào, kết quả xác minh ra sao.'),
            ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->model($this->getRecord())
            ->operation('edit')
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('confirmReunion')
                    ->footer([
                        SchemaActions::make($this->getFormActions())
                            ->key('form-actions'),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('confirm')
                ->label('Xác nhận đoàn tụ hộ')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->submit('confirmReunion')
                ->disabled(fn (): bool => ! $this->getRecord()->isHandedOver()),

            Action::make('cancel')
                ->label('Hủy')
                ->color('gray')
                ->url(fn (): string => $this->getResourceUrl('view')),
        ];
    }

    public function confirmReunion(): void
    {
        $record = $this->getRecord();

        if (! $record->isHandedOver()) {
            Notification::make()
                ->title('Không thể xác nhận đoàn tụ')
                ->body('Chỉ hồ sơ đang ở trạng thái "Đã bàn giao" mới được xác nhận đoàn tụ hộ.')
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        // Logic dùng chung với table action; observer StatusChangeAuditObserver
        // kiểm tra reason >= 20 ký tự và ghi audit_logs (actor_role = ADMIN).
        $record->confirmReunionByAdmin($data['reason'], Auth::id());

        Cache::forget('dashboard_stats_overview');

        Notification::make()
            ->title('Đã xác nhận đoàn tụ (admin)')
            ->success()
            ->send();

        $this->redirect($this->getResourceUrl('view'));
    }
}
