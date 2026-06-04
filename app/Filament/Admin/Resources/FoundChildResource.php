<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FoundChildResource\Pages;
use App\Filament\Admin\Resources\FoundChildResource\RelationManagers\PhotosRelationManager;
use App\Filament\Admin\Resources\FoundChildResource\Schemas\FoundChildInfolist;
use App\Models\FoundChild;
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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class FoundChildResource extends Resource
{
    protected static ?string $model = FoundChild::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static UnitEnum|string|null $navigationGroup = 'Nội dung';

    public static function getModelLabel(): string
    {
        return 'hồ sơ trẻ tìm được';
    }

    public static function getPluralModelLabel(): string
    {
        return 'hồ sơ trẻ tìm được';
    }

    public static function getNavigationLabel(): string
    {
        return 'Hồ sơ trẻ tìm được';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('province_id')->label('Tỉnh/Thành')->relationship('province', 'name')->required(),
            DatePicker::make('found_date')->label('Ngày phát hiện')->required(),
            TextInput::make('found_time')->label('Giờ phát hiện')->placeholder('HH:MM'),
            TextInput::make('age_estimate')->label('Tuổi ước tính'),
            Select::make('gender_estimate')->label('Giới tính ước tính')->options(['MALE' => 'Nam', 'FEMALE' => 'Nữ', 'UNKNOWN' => 'Không rõ']),
            Textarea::make('condition_desc')->label('Mô tả tình trạng'),
            Textarea::make('clothes_desc')->label('Mô tả quần áo'),
            Textarea::make('special_marks')->label('Dấu hiệu đặc biệt'),
            TextInput::make('current_location')->label('Vị trí hiện tại'),
            TextInput::make('handover_agency')->label('Cơ quan/Nơi bàn giao'),
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
                ->default('PENDING'),
            FileUpload::make('photo_upload')
                ->label('Ảnh chính')
                ->image()
                ->disk('public')
                ->directory(fn ($record) => 'found_children/'.($record?->id ?? 'tmp'))
                ->multiple()
                ->maxFiles(20)
                ->imagePreviewHeight(80),
        ]);
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
                    ->formatStateUsing(function ($state) {
                        $map = [
                            'PENDING' => ['label' => 'Đang chờ', 'class' => 'bg-yellow-100 text-yellow-800'],
                            'APPROVED' => ['label' => 'Đã duyệt', 'class' => 'bg-green-100 text-green-800'],
                            'MATCHED' => ['label' => 'Đã khớp', 'class' => 'bg-green-100 text-green-800'],
                            'FOUND' => ['label' => 'Đã tìm thấy', 'class' => 'bg-blue-100 text-blue-800'],
                            'REJECTED' => ['label' => 'Bị từ chối', 'class' => 'bg-red-100 text-red-800'],
                            'ACTIVE' => ['label' => 'Đang hoạt động', 'class' => 'bg-blue-100 text-blue-800'],
                            'RESOLVED' => ['label' => 'Đã xử lý', 'class' => 'bg-indigo-100 text-indigo-800'],
                            'CLOSED' => ['label' => 'Đã đóng', 'class' => 'bg-gray-100 text-gray-800'],
                        ];
                        $s = $map[$state] ?? ['label' => (string) $state, 'class' => 'bg-gray-100 text-gray-800'];

                        return sprintf('<span class="px-2 py-1 rounded text-xs %s">%s</span>', $s['class'], $s['label']);
                    })
                    ->html()
                    ->sortable(),
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
                    ->action(fn (FoundChild $record) => $record->update([
                        'status' => 'APPROVED',
                        'approved_at' => now(),
                        'approved_by' => Auth::id(),
                    ])),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFoundChildren::route('/'),
            'create' => Pages\CreateFoundChild::route('/create'),
            'edit' => Pages\EditFoundChild::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'province:id,name',
                'user:id,full_name',
                'approvedBy:id,full_name',
            ]);
    }
}
