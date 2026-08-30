<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FoundChildResource\Pages;
use App\Filament\Admin\Resources\FoundChildResource\RelationManagers\PhotosRelationManager;
use App\Filament\Admin\Resources\FoundChildResource\Schemas\FoundChildInfolist;
use App\Models\FoundChild;
use App\Rules\SecureImage;
use App\Support\SecureUploadFilename;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Admin\Resources\FoundChildResource\RelationManagers\MatchedMissingChildrenRelationManager;
use App\Filament\Forms\Components\LocationPicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class FoundChildResource extends Resource
{
    protected static ?string $model = FoundChild::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static UnitEnum|string|null $navigationGroup = 'Nội dung';

    public static function getModelLabel(): string
    {
        return 'Hồ Sơ Trẻ Đã Tìm Thấy';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Hồ Sơ Trẻ Đã Tìm Thấy';
    }

    public static function getNavigationLabel(): string
    {
        return 'Hồ Sơ Trẻ Đã Tìm Thấy';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Cache::remember('badge_found_pending', 300, function () {
            return FoundChild::where('status', FoundChild::STATUS_PENDING)->count();
        });

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = (int) Cache::get('badge_found_pending', 0);

        return $count > 10 ? 'danger' : 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.forms.components.record-hero'),

            // BLOCK 1 — Thông tin phát hiện
            Section::make('Thông tin phát hiện')
                ->icon('heroicon-o-magnifying-glass')
                ->schema([
                    Grid::make(2)->schema([
                        DatePicker::make('found_date')
                            ->label('Ngày phát hiện')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        TextInput::make('found_time')
                            ->label('Giờ phát hiện')
                            ->placeholder('HH:MM'),
                    ]),

                    Grid::make(3)->schema([
                        TextInput::make('age_estimate')
                            ->label('Tuổi ước tính')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(18)
                            ->suffix('tuổi')
                            ->placeholder('Ví dụ: 7'),

                        Select::make('gender_estimate')
                            ->label('Giới tính ước tính')
                            ->options([
                                'MALE' => 'Nam',
                                'FEMALE' => 'Nữ',
                                'UNKNOWN' => 'Không rõ',
                            ])
                            ->placeholder('Chọn…')
                            ->native(false),

                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'PENDING' => 'Đang chờ',
                                'APPROVED' => 'Đã duyệt',
                                'MATCHED' => 'Đã khớp',
                                'FOUND' => 'Đã tìm thấy',
                                'REJECTED' => 'Bị từ chối',
                                'ACTIVE' => 'Đang hoạt động',
                                'RESOLVED' => 'Đã xử lý',
                                'CLOSED' => 'Đã đóng',
                            ])
                            ->required()
                            ->default('PENDING')
                            ->native(false),
                    ]),
                ]),

            // BLOCK 2 — Đặc điểm nhận dạng
            Section::make('Đặc điểm nhận dạng')
                ->icon('heroicon-o-eye')
                ->description('Mô tả càng chi tiết càng giúp đối chiếu với hồ sơ mất tích.')
                ->schema([
                    Grid::make(3)->schema([
                        Textarea::make('condition_desc')
                            ->label('Mô tả tình trạng')
                            ->rows(3)
                            ->placeholder('Ví dụ: trẻ khỏe mạnh, không bị thương…')
                            ->maxLength(1000),

                        Textarea::make('clothes_desc')
                            ->label('Mô tả quần áo')
                            ->rows(3)
                            ->placeholder('Ví dụ: áo thun xanh, quần short đen…')
                            ->maxLength(500),

                        Textarea::make('special_marks')
                            ->label('Dấu hiệu đặc biệt')
                            ->rows(3)
                            ->placeholder('Ví dụ: sẹo nhỏ ở cằm trái…')
                            ->maxLength(500),
                    ]),
                ]),

            // BLOCK 3 — Khu vực & bàn giao
            Section::make('Khu vực & bàn giao')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Select::make('province_id')
                        ->label('Tỉnh/Thành')
                        ->relationship('province', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->placeholder('Tìm tỉnh thành…')
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        // U-01/V-02 — Xã/Phường KHÔNG phải input tự do: giá trị do
                        // reverse-geocode nội bộ suy ra từ lat/lng. Hiển thị chỉ đọc.
                        TextInput::make('ward')
                            ->label('Xã/Phường (tự động từ toạ độ)')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Xác định tự động từ vị trí ghim trên bản đồ'),

                        TextInput::make('current_location')
                            ->label('Vị trí hiện tại')
                            ->placeholder('Ví dụ: Bến xe Miền Đông…')
                            ->maxLength(255),
                    ]),

                    TextInput::make('address_detail')
                        ->label('Ghi chú địa điểm (tùy chọn)')
                        ->maxLength(500)
                        ->helperText('Mô tả thêm giúp nhận diện hiện trường.')
                        ->placeholder('Ví dụ: gần cổng sau chợ…')
                        ->columnSpanFull(),

                    TextInput::make('handover_agency')
                        ->label('Cơ quan/Nơi bàn giao')
                        ->placeholder('Ví dụ: Công an Phường X…')
                        ->maxLength(255),
                ]),

            // BLOCK 4 — Vị trí trên bản đồ (thay nhập lat/lng tay — V-01)
            Section::make('Vị trí trên bản đồ')
                ->description('Chạm/click vào bản đồ hoặc kéo marker để chọn vị trí. Vĩ độ và kinh độ được cập nhật tự động.')
                ->schema([
                    LocationPicker::make('location_picker')
                        ->label('Chọn vị trí')
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('lat')
                            ->label('Vĩ độ')
                            ->numeric()
                            ->step('any')
                            ->placeholder('10.7769'),

                        TextInput::make('lng')
                            ->label('Kinh độ')
                            ->numeric()
                            ->step('any')
                            ->placeholder('106.7009'),
                    ]),
                ]),

            // BLOCK 5 — Ảnh đại diện
            Section::make('Ảnh đại diện')
                ->icon('heroicon-o-photo')
                ->schema([
                    FileUpload::make('photo_upload')
                        ->label('Ảnh chính')
                        ->image()
                        ->disk('public')
                        ->directory(fn ($record) => 'found_children/'.($record?->id ?? 'tmp'))
                        ->multiple()
                        ->maxFiles(20)
                        ->maxSize(5120)
                        ->rules([new SecureImage])
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file): string => SecureUploadFilename::for($file),
                        )
                        ->imagePreviewHeight(80),
                ]),

        ])->dense();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Mã')->sortable(),
                TextColumn::make('found_date')->label('Ngày phát hiện')->date()->sortable(),
                TextColumn::make('province.name')->label('Tỉnh/Thành')->sortable(),
                TextColumn::make('user.full_name')->label('Người báo'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'PENDING' => 'Đang chờ',
                        'APPROVED' => 'Đã duyệt',
                        'MATCHED' => 'Đã khớp',
                        'FOUND' => 'Đã tìm thấy',
                        'REJECTED' => 'Bị từ chối',
                        'ACTIVE' => 'Đang hoạt động',
                        'RESOLVED' => 'Đã xử lý',
                        'CLOSED' => 'Đã đóng',
                        default => (string) $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'MATCHED' => 'success',
                        'FOUND' => 'info',
                        'REJECTED' => 'danger',
                        'ACTIVE' => 'info',
                        'RESOLVED' => 'gray',
                        'CLOSED' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'PENDING' => 'Chờ duyệt',
                        'APPROVED' => 'Đã duyệt',
                        'MATCHED' => 'Đã khớp',
                        'CLOSED' => 'Đã đóng',
                    ]),
                SelectFilter::make('province_id')
                    ->label('Tỉnh/Thành')
                    ->relationship('province', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('gender_estimate')
                    ->label('Giới tính ước tính')
                    ->options([
                        'MALE' => 'Nam',
                        'FEMALE' => 'Nữ',
                        'UNKNOWN' => 'Không rõ',
                    ]),
                Filter::make('found_date')
                    ->label('Ngày phát hiện')
                    ->schema([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $query): Builder => $query->whereDate('found_date', '>=', $data['from']))
                        ->when(filled($data['until'] ?? null), fn (Builder $query): Builder => $query->whereDate('found_date', '<=', $data['until']))),
                Filter::make('ai_processed')
                    ->label('Đã AI xử lý')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('ai_processed_at')),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (FoundChild $record): bool => $record->status === 'PENDING')
                    ->action(function (FoundChild $record) {
                        // Duyệt → FoundChildObserver::updated() phát thông báo FOUND_APPROVED:
                        // người báo tin luôn nhận; gia đình có hồ sơ khớp (FaceMatchResult
                        // CONFIRMED) cũng nhận. Mọi đường vào đều được phủ.
                        $record->update([
                            'status' => FoundChild::STATUS_APPROVED,
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                        ]);
                    }),
                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (FoundChild $record): bool => $record->status === 'PENDING')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->required()
                            ->rows(3)
                            ->helperText('Lý do sẽ được gửi cho người báo tin.'),
                    ])
                    ->action(function (FoundChild $record, array $data) {
                        // Lý do được lưu vào rejection_reason (cột vừa được bổ sung)
                        // và FoundChildObserver gửi kèm cho người báo tin.
                        $record->update([
                            'status' => FoundChild::STATUS_REJECTED,
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FoundChildInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            PhotosRelationManager::class,
            MatchedMissingChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFoundChildren::route('/'),
            'view' => Pages\ViewFoundChild::route('/{record}'),
            'create' => Pages\CreateFoundChild::route('/create'),
            'edit' => Pages\EditFoundChild::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'province:id,name',
                // Giữ nguyên id,full_name (dùng hiển thị bảng) và bổ sung role,email:
                // observer thông báo (FOUND_APPROVED/FOUND_REJECTED) dùng
                // $foundChild->user->role làm role_at_time (cột NOT NULL) và email
                // để gửi mail. Nếu eager-load thiếu role, deliver() insert NULL vào
                // role_at_time → vi phạm NOT NULL → recipient không tạo → chuông admin
                // không báo (lỗi đã điều tra: FOUND_REJECTED #28).
                'user:id,full_name,role,email',
                'approvedBy:id,full_name',
            ]);
    }
}
