<?php

namespace App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ThirdPartyVerifierProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.full_name')
                    ->label('Verifier')
                    ->description(fn ($record): ?string => $record->user?->email)
                    ->searchable(['full_name', 'email'])
                    ->sortable(),

                TextColumn::make('organization_type')
                    ->label('Loại tổ chức')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'POLICE' => 'Công an',
                        'GOVERNMENT' => 'Cơ quan nhà nước',
                        'NGO' => 'Tổ chức NGO',
                        default => $state ?? 'Không xác định',
                    }),

                TextColumn::make('organization_name')
                    ->label('Tên đơn vị')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('badge_or_staff_id')
                    ->label('Mã số hiệu')
                    ->placeholder('Chưa cập nhật')
                    ->searchable(),

                TextColumn::make('handoverPoint.name')
                    ->label('Điểm bàn giao phụ trách')
                    ->description(fn ($record): ?string => $record->handoverPoint?->address)
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('verifiedByAdmin.full_name')
                    ->label('Admin xác minh')
                    ->placeholder('Chưa xác minh')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('verified_at')
                    ->label('Thời gian xác minh')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Chưa xác minh')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('organization_type')
                    ->label('Loại tổ chức')
                    ->options([
                        'POLICE' => 'Công an',
                        'GOVERNMENT' => 'Cơ quan nhà nước',
                        'NGO' => 'Tổ chức NGO',
                    ]),

                SelectFilter::make('handover_point_id')
                    ->label('Điểm bàn giao')
                    ->relationship('handoverPoint', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Chỉnh sửa'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}