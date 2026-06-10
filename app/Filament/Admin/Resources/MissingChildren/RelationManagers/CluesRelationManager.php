<?php

namespace App\Filament\Admin\Resources\MissingChildren\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CluesRelationManager extends RelationManager
{
    protected static string $relationship = 'clues';

    protected static ?string $title = 'Manh mối';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('clue_type')
                    ->label('Loại')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'SIGHTING' => 'success',
                        'PHOTO' => 'info',
                        'VIDEO' => 'warning',
                        'RUMOR' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'SIGHTING' => 'Mục kích',
                        'RUMOR' => 'Tin đồn',
                        'PHOTO' => 'Hình ảnh',
                        'VIDEO' => 'Video',
                        'OTHER' => 'Khác',
                        default => $state,
                    }),

                TextColumn::make('description')
                    ->label('Mô tả')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->description)
                    ->searchable(),

                TextColumn::make('gps_status')
                    ->label('GPS')
                    ->badge()
                    ->getStateUsing(fn($record) => $record->lat && $record->lng ? 'Có GPS' : 'Không có')
                    ->color(fn($state) => $state === 'Có GPS' ? 'success' : 'gray'),

                TextColumn::make('location_accuracy_m')
                    ->label('Độ chính xác')
                    ->formatStateUsing(fn($state) => $state ? '±' . $state . 'm' : '—')
                    ->placeholder('—'),

                ImageColumn::make('image_path')
                    ->label('Ảnh')
                    ->circular(),

                TextColumn::make('is_verified')
                    ->label('Xác minh')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn($state) => $state ? 'Đã xác minh' : 'Chưa xác minh'),

                TextColumn::make('is_hidden')
                    ->label('Ẩn/Hiện')
                    ->badge()
                    ->color(fn($state) => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn($state) => $state ? 'Đang ẩn' : 'Đang hiện'),

                TextColumn::make('occurred_at')
                    ->label('Thời điểm')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('clue_type')
                    ->label('Loại manh mối')
                    ->options([
                        'SIGHTING' => 'Mục kích',
                        'RUMOR' => 'Tin đồn',
                        'PHOTO' => 'Hình ảnh',
                        'VIDEO' => 'Video',
                        'OTHER' => 'Khác',
                    ]),

                SelectFilter::make('is_verified')
                    ->label('Xác minh')
                    ->options([
                        '1' => 'Đã xác minh',
                        '0' => 'Chưa xác minh',
                    ]),

                SelectFilter::make('is_hidden')
                    ->label('Ẩn/Hiện')
                    ->options([
                        '1' => 'Đang ẩn',
                        '0' => 'Đang hiện',
                    ]),

                Filter::make('occurred_at')
                    ->schema([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $q, $date) => $q->whereDate('occurred_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn(Builder $q, $date) => $q->whereDate('occurred_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([

                // ── Xem chi tiết — modal nổi lên, có bản đồ ──────────────
                Action::make('view_detail')
                    ->label('Xem chi tiết')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Manh mối — ' . match ($record->clue_type) {
                        'SIGHTING' => 'Mục kích',
                        'RUMOR' => 'Tin đồn',
                        'PHOTO' => 'Hình ảnh',
                        'VIDEO' => 'Video',
                        default => 'Khác',
                    })
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->infolist(fn(Schema $schema, $record) => $schema->components([

                        // Nội dung + trạng thái
                        Section::make('Nội dung manh mối')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('clue_type')
                                        ->label('Loại')
                                        ->badge()
                                        ->color(fn($state) => match ($state) {
                                            'SIGHTING' => 'success',
                                            'PHOTO' => 'info',
                                            'VIDEO' => 'warning',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn($state) => match ($state) {
                                            'SIGHTING' => 'Mục kích',
                                            'RUMOR' => 'Tin đồn',
                                            'PHOTO' => 'Hình ảnh',
                                            'VIDEO' => 'Video',
                                            default => 'Khác',
                                        }),

                                    TextEntry::make('is_verified')
                                        ->label('Xác minh')
                                        ->badge()
                                        ->color(fn($state) => $state ? 'success' : 'warning')
                                        ->formatStateUsing(fn($state) => $state ? '✓ Đã xác minh' : 'Chưa xác minh'),

                                    TextEntry::make('is_hidden')
                                        ->label('Trạng thái')
                                        ->badge()
                                        ->color(fn($state) => $state ? 'danger' : 'success')
                                        ->formatStateUsing(fn($state) => $state ? 'Đang ẩn' : 'Đang hiện'),
                                ]),

                                TextEntry::make('description')
                                    ->label('Mô tả chi tiết')
                                    ->columnSpanFull(),

                                TextEntry::make('occurred_at')
                                    ->label('Thời điểm xảy ra')
                                    ->dateTime('H:i — d/m/Y')
                                    ->placeholder('Không rõ'),
                            ]),

                        // Ảnh (nếu có)
                        Section::make('Hình ảnh đính kèm')
                            ->icon('heroicon-o-photo')
                            ->visible(fn() => (bool) $record->image_path)
                            ->schema([
                                ImageEntry::make('image_path')
                                    ->label('')
                                    ->disk('public')
                                    ->height(240)
                                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                            ]),

                        // Vị trí GPS + bản đồ
                        Section::make('Vị trí GPS')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('lat')
                                        ->label('Vĩ độ')
                                        ->placeholder('Chưa có'),

                                    TextEntry::make('lng')
                                        ->label('Kinh độ')
                                        ->placeholder('Chưa có'),

                                    TextEntry::make('location_accuracy_m')
                                        ->label('Độ chính xác')
                                        ->formatStateUsing(fn($state) => $state ? '±' . $state . ' m' : '—')
                                        ->placeholder('—'),
                                ]),

                                // Embed bản đồ OpenStreetMap (iframe, không cần API key)
                                TextEntry::make('map_embed')
                                    ->label('Bản đồ')
                                    ->visible(fn() => $record->lat && $record->lng)
                                    ->state(fn() => $record->lat && $record->lng
                                        ? '<iframe
                                                width="100%"
                                                height="320"
                                                style="border:0;border-radius:8px;"
                                                loading="lazy"
                                                src="https://www.openstreetmap.org/export/embed.html?bbox=' . ($record->lng - 0.005) . ',' . ($record->lat - 0.005) . ',' . ($record->lng + 0.005) . ',' . ($record->lat + 0.005) . '&layer=mapnik&marker=' . $record->lat . ',' . $record->lng . '"
                                            ></iframe>
                                            <div style="margin-top:6px">
                                                <a href="https://www.google.com/maps?q=' . $record->lat . ',' . $record->lng . '"
                                                   target="_blank"
                                                   style="color:#3b82f6;font-size:13px;">
                                                    📍 Mở Google Maps
                                                </a>
                                            </div>'
                                        : '')
                                    ->html()
                                    ->columnSpanFull(),

                                TextEntry::make('no_gps')
                                    ->label('')
                                    ->visible(fn() => !$record->lat || !$record->lng)
                                    ->state('Manh mối này không có tọa độ GPS.')
                                    ->color('gray'),
                            ]),

                        // Người gửi + xác minh
                        Section::make('Thông tin người gửi')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('display_name')
                                        ->label('Người gửi')
                                        ->getStateUsing(fn() => $record->is_anonymous || !$record->user
                                            ? 'Ẩn danh'
                                            : ($record->user->full_name ?? $record->user->name ?? 'Ẩn danh')),

                                    TextEntry::make('verifiedBy.full_name')
                                        ->label('Xác minh bởi')
                                        ->placeholder('Chưa xác minh'),

                                    TextEntry::make('verified_at')
                                        ->label('Xác minh lúc')
                                        ->dateTime('H:i d/m/Y')
                                        ->placeholder('—'),
                                ]),

                                TextEntry::make('created_at')
                                    ->label('Gửi lúc')
                                    ->dateTime('H:i — d/m/Y'),
                            ]),

                    ])),

                // ── Xác minh ─────────────────────────────────────────────
                Action::make('verify')
                    ->label('Xác minh')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => !$record->is_verified)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'is_verified' => true,
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                        ]);
                    }),

                // ── Ẩn / Hiện ────────────────────────────────────────────
                Action::make('toggle_hidden')
                    ->label(fn($record) => $record->is_hidden ? 'Hiện' : 'Ẩn')
                    ->icon(fn($record) => $record->is_hidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn($record) => $record->is_hidden ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'is_hidden' => !$record->is_hidden,
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordClasses(fn($record) => $record->is_hidden ? 'opacity-50' : null)
            ->defaultSort('created_at', 'desc');
    }
}