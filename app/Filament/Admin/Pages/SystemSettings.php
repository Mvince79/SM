<?php

namespace App\Filament\Admin\Pages;

use App\Settings\SettingRegistry;
use App\Settings\SettingService;
use BackedEnum;
use Filament\Actions\Action;
use UnitEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * Trang admin "Cài đặt hệ thống" — nơi admin chỉnh các thông số trước đây nằm
 * trong config (tần suất nhắc nhở & tham số AI so khớp) mà KHÔNG phải sửa code.
 *
 * Mọi input đều có "rào chắn" (min/max/step/options + validate chéo ở service)
 * nên không thể nhập giá trị phi logic. Mỗi thay đổi ghi Nhật ký kiểm toán.
 */
class SystemSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Cài đặt hệ thống';

    protected static UnitEnum|string|null $navigationGroup = 'Cấu hình';

    protected static ?string $title = 'Cài đặt hệ thống';

    protected static ?string $slug = 'system-settings';

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingService::class)->all();

        $data = [];
        foreach (SettingRegistry::all() as $field => $def) {
            $value = $settings[$field] ?? $def['default'];

            if ($def['type'] === 'milestones') {
                $data[$field] = array_map(fn ($m): array => ['value' => $m], (array) $value);

                continue;
            }

            $data[$field] = $value;
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('settings')
                    ->tabs([
                        Tab::make('Nhắc nhở & thông báo')
                            ->icon(Heroicon::OutlinedBell)
                            ->schema([
                                Section::make('Tần suất nhắc người nhà cập nhật trạng thái')
                                    ->description('Chỉnh khoảng cách, mốc và đơn vị thời gian cho các nhắc nhở xác nhận bàn giao / đoàn tụ. Chọn đơn vị nhỏ (giây/phút) để test nhanh khi chưa deploy.')
                                    ->schema($this->buildFields(SettingRegistry::GROUP_NOTIFICATION)),
                            ]),
                        Tab::make('Tham số AI so khớp')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->schema([
                                Section::make('Ngưỡng & thông số AI nhận diện khuôn mặt')
                                    ->description('Điều chỉnh độ nhạy gợi ý khớp, ngưỡng hiển thị tìm kiếm công khai và các tham số gọi AI. Các giá trị bị giới hạn trong vùng an toàn.')
                                    ->schema($this->buildFields(SettingRegistry::GROUP_FACE_MATCH)),
                            ]),
                        Tab::make('Tìm kiếm bằng hình ảnh')
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->schema([
                                Section::make('Giới hạn tốc độ tìm kiếm bằng hình ảnh')
                                    ->description('Giới hạn số lần một IP được tìm kiếm bằng hình ảnh trong một khoảng thời gian, để bảo vệ quota AI và chống lạm dụng. Đồng bộ với middleware throttle của route /api/photo-search.')
                                    ->schema($this->buildFields(SettingRegistry::GROUP_PHOTO_SEARCH)),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return array<int, \Filament\Schemas\Component>
     */
    private function buildFields(string $group): array
    {
        $fields = [];

        foreach (SettingRegistry::group($group) as $field => $def) {
            $fields[] = match ($def['type']) {
                'select' => Select::make((string) $field)
                    ->label($def['label'])
                    ->helperText($def['help'])
                    ->options($def['options'])
                    ->required()
                    ->native(false)
                    ->searchable(false),
                'milestones' => Repeater::make((string) $field)
                    ->label($def['label'])
                    ->helperText($def['help'])
                    ->schema([
                        TextInput::make('value')
                            ->label('Mốc')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->addActionLabel('Thêm mốc nhắc')
                    ->reorderable()
                    ->collapsible()
                    ->maxItems(5)
                    ->itemLabel(fn (array $state): ?string => isset($state['value']) ? 'Mốc '.$state['value'] : null),
                'float' => TextInput::make((string) $field)
                    ->label($def['label'])
                    ->helperText($def['help'])
                    ->numeric()
                    ->minValue($def['min'])
                    ->maxValue($def['max'])
                    ->required()
                    ->suffix($def['unit'] ?? null),
                default => TextInput::make((string) $field)
                    ->label($def['label'])
                    ->helperText($def['help'])
                    ->numeric()
                    ->integer()
                    ->minValue($def['min'])
                    ->maxValue($def['max'])
                    ->required()
                    ->suffix($def['unit'] ?? null),
            };
        }

        return $fields;
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
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
            Action::make('save')
                ->label('Lưu thay đổi')
                ->icon('heroicon-o-check')
                ->color('success')
                ->submit('save'),

            Action::make('reset')
                ->label('Khôi phục mặc định')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Khôi phục về mặc định?')
                ->modalDescription('Mọi thông số sẽ quay về giá trị mặc định trong cấu hình. Thao tác này được ghi vào Nhật ký kiểm toán.')
                ->action(fn (): null => $this->resetToDefaults()),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Repeater trả về [['value'=>5],['value'=>10]] → chuyển về [5,10].
        foreach (SettingRegistry::all() as $field => $def) {
            if ($def['type'] !== 'milestones') {
                continue;
            }

            $items = $data[$field] ?? [];
            $data[$field] = array_values(array_filter(
                array_map(fn ($item): ?int => isset($item['value']) ? (int) $item['value'] : null, (array) $items)
            ));
        }

        try {
            app(SettingService::class)->setMany($data);
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Cài đặt không hợp lệ')
                ->body(implode(' ', $e->errors()))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Đã lưu cài đặt hệ thống')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    public function resetToDefaults(): void
    {
        app(SettingService::class)->resetAll();

        Notification::make()
            ->title('Đã khôi phục về mặc định')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }
}
