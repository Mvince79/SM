<?php

namespace App\Filament\Admin\Resources\MissingChildren\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Tables\Filters\TrashedFilter;
use App\Models\MissingChild;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Mail;
use App\Mail\MissingChildApproved;
use App\Mail\MissingChildRejected;

class MissingChildrenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case_code')
                    ->label('Mã hồ sơ')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('full_name')
                    ->label('Họ tên')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('age_estimate')
                    ->label('Tuổi')
                    ->sortable(),

                TextColumn::make('gender')
                    ->label('Giới tính')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'MALE' => 'Nam',
                        'FEMALE' => 'Nữ',
                        default => 'Không rõ',
                    }),

                TextColumn::make('province.name')
                    ->label('Tỉnh thành')
                    ->sortable(),

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
                SelectFilter::make('status')->label('Trạng thái')
                    ->options([
                        'PENDING' => 'Chờ duyệt',
                        'APPROVED' => 'Đã duyệt',
                        'FOUND' => 'Đã tìm thấy',
                        'CLOSED' => 'Đóng',
                        'REJECTED' => 'Từ chối',
                    ]),
                SelectFilter::make('gender')->label('Giới tính')
                    ->options(['MALE' => 'Nam', 'FEMALE' => 'Nữ', 'UNKNOWN' => 'Không rõ']),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),

                EditAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),

                Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(MissingChild $record) => $record->status === 'PENDING')
                    ->action(function (MissingChild $record) {
                        $record->update([
                            'status' => 'APPROVED',
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                        ]);

                        Mail::to($record->user->email)
                            ->queue(new MissingChildApproved($record));
                    }),

                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(MissingChild $record) => $record->status === 'PENDING')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->required(),
                    ])
                    ->action(function (MissingChild $record, array $data) {
                        $record->update([
                            'status' => 'REJECTED',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        Mail::to($record->user->email)
                            ->queue(new MissingChildRejected($record));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
