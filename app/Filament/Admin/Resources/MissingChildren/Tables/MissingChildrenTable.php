<?php

namespace App\Filament\Admin\Resources\MissingChildren\Tables;

use App\Mail\MissingChildApproved;
use App\Mail\MissingChildRejected;
use App\Models\MissingChild;
use App\Models\Province;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Events\PostStatusChanged;
use Illuminate\Support\Facades\Mail;

class MissingChildrenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('primaryPhoto.path_thumb')
                    ->label('')
                    ->defaultImageUrl(
                        fn(MissingChild $record) =>
                        'https://ui-avatars.com/api/?name=' . urlencode($record->case_code)
                        . '&size=48&background=e2e8f0&color=64748b&bold=true'
                    )
                    ->circular()
                    ->imageSize(48)
                    ->disk('public'),

                TextColumn::make('case_code')
                    ->label('Mã hồ sơ')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('full_name')
                    ->label('Họ tên')
                    ->searchable()
                    ->sortable()
                    ->description(fn(MissingChild $record) => $record->province?->name),

                TextColumn::make('age_estimate')
                    ->label('Tuổi')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('gender')
                    ->label('Giới tính')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'MALE' => 'Nam',
                        'FEMALE' => 'Nữ',
                        default => 'Không rõ',
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'MALE' => 'info',
                        'FEMALE' => 'pink',
                        default => 'gray',
                    }),

                TextColumn::make('missing_date')
                    ->label('Ngày mất tích')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'APPROVED' => 'success',
                        'PENDING' => 'warning',
                        'REJECTED' => 'danger',
                        'FOUND' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'PENDING' => 'Chờ duyệt',
                        'APPROVED' => 'Đã duyệt',
                        'FOUND' => 'Đã tìm thấy',
                        'CLOSED' => 'Đóng',
                        'REJECTED' => 'Từ chối',
                        default => $state,
                    }),

                TextColumn::make('clues_count')
                    ->label('Manh mối')
                    ->counts('clues')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('comments_count')
                    ->label('Bình luận')
                    ->counts('comments')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label('Ngày duyệt')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'PENDING' => 'Chờ duyệt',
                        'APPROVED' => 'Đã duyệt',
                        'FOUND' => 'Đã tìm thấy',
                        'CLOSED' => 'Đóng',
                        'REJECTED' => 'Từ chối',
                    ])
                    ->columnSpan(1),

                SelectFilter::make('gender')
                    ->label('Giới tính')
                    ->options([
                        'MALE' => 'Nam',
                        'FEMALE' => 'Nữ',
                        'UNKNOWN' => 'Không rõ',
                    ])
                    ->columnSpan(1),

                SelectFilter::make('skin_tone')
                    ->label('Màu da')
                    ->options([
                        'LIGHT' => 'Sáng',
                        'MEDIUM' => 'Trung bình',
                        'DARK' => 'Tối',
                    ])
                    ->columnSpan(1),

                SelectFilter::make('province_id')
                    ->label('Tỉnh / Thành')
                    ->options(fn() => Province::orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->columnSpan(1),

                Filter::make('missing_date')
                    ->label('Ngày mất tích')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Từ ngày')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Đến ngày')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('missing_date', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('missing_date', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data) {
                        $parts = [];
                        if ($data['from'])
                            $parts[] = 'Từ ' . $data['from'];
                        if ($data['until'])
                            $parts[] = 'Đến ' . $data['until'];
                        return implode(' — ', $parts) ?: null;
                    }),

                Filter::make('age_estimate')
                    ->label('Tuổi ước tính')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('age_min')
                            ->label('Tuổi từ')
                            ->numeric()
                            ->minValue(0),
                        \Filament\Forms\Components\TextInput::make('age_max')
                            ->label('Tuổi đến')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['age_min'], fn($q) => $q->where('age_estimate', '>=', (int) $data['age_min']))
                            ->when($data['age_max'], fn($q) => $q->where('age_estimate', '<=', (int) $data['age_max']));
                    })
                    ->indicateUsing(function (array $data) {
                        $parts = [];
                        if ($data['age_min'])
                            $parts[] = 'Tuổi ≥ ' . $data['age_min'];
                        if ($data['age_max'])
                            $parts[] = 'Tuổi ≤ ' . $data['age_max'];
                        return implode(', ', $parts) ?: null;
                    }),

                TrashedFilter::make()->columnSpan(1),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 2,
                'sm' => 3,
                'md' => 4,
                'lg' => 6,
                'xl' => 9,
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),

                Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Duyệt hồ sơ?')
                    ->modalDescription(fn(MissingChild $record) => "Duyệt hồ sơ \"{$record->full_name}\" ({$record->case_code})?")
                    ->visible(fn(MissingChild $record) => $record->status === 'PENDING')
                    ->action(function (MissingChild $record) {
                        $record->update([
                            'status' => 'APPROVED',
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                        ]);
                        if ($record->user?->email) {
                            Mail::to($record->user->email)->queue(new MissingChildApproved($record));
                        }
                        Cache::forget('badge_missing_pending');
                        Notification::make()->title('Đã duyệt hồ sơ')->success()->send();
                        PostStatusChanged::dispatch($record->refresh());
                    }),

                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(MissingChild $record) => $record->status === 'PENDING')
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (MissingChild $record, array $data) {
                        $record->update([
                            'status' => 'REJECTED',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        if ($record->user?->email) {
                            Mail::to($record->user->email)->queue(new MissingChildRejected($record));
                        }
                        Cache::forget('badge_missing_pending');
                        Notification::make()->title('Đã từ chối hồ sơ')->danger()->send();
                        PostStatusChanged::dispatch($record->refresh());
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('Duyệt hàng loạt')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Duyệt các hồ sơ đã chọn?')
                        ->modalDescription('Chỉ các hồ sơ đang ở trạng thái "Chờ duyệt" mới được xử lý.')
                        ->action(function (Collection $records) {
                            $count = 0;
                            $records->each(function (MissingChild $record) use (&$count) {
                                if ($record->status !== 'PENDING')
                                    return;
                                $record->update([
                                    'status' => 'APPROVED',
                                    'approved_at' => now(),
                                    'approved_by' => Auth::id(),
                                ]);
                                if ($record->user?->email) {
                                    Mail::to($record->user->email)->queue(new MissingChildApproved($record));
                                }
                                PostStatusChanged::dispatch($record->refresh());
                                $count++;
                            });
                            Cache::forget('badge_missing_pending');
                            Notification::make()->title("Đã duyệt {$count} hồ sơ")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_reject')
                        ->requiresConfirmation()
                        ->label('Từ chối hàng loạt')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->schema([
                            Textarea::make('rejection_reason')
                                ->label('Lý do từ chối')
                                ->required()
                                ->rows(3)
                                ->placeholder('Nhập lý do áp dụng cho tất cả hồ sơ được chọn...'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $count = 0;
                            $records->each(function (MissingChild $record) use ($data, &$count) {
                                if ($record->status !== 'PENDING')
                                    return;
                                $record->update([
                                    'status' => 'REJECTED',
                                    'rejection_reason' => $data['rejection_reason'],
                                ]);
                                if ($record->user?->email) {
                                    Mail::to($record->user->email)->queue(new MissingChildRejected($record));
                                }
                                PostStatusChanged::dispatch($record->refresh());
                                $count++;
                            });
                            Cache::forget('badge_missing_pending');
                            Notification::make()->title("Đã từ chối {$count} hồ sơ")->danger()->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()->label('Xóa'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
