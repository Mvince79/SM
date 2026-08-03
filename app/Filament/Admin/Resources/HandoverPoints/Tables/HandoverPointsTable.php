<?php

namespace App\Filament\Admin\Resources\HandoverPoints\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HandoverPointsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên điểm bàn giao')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Loại')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'POLICE_STATION' => 'Công an',
                        'COMMUNE_PEOPLE_COMMITTEE' => 'UBND xã/phường',
                        'HOTLINE_111' => 'Tổng đài Quốc gia 111',
                        'NGO' => 'Tổ chức NGO',
                        'OTHER' => 'Đơn vị khác',
                        default => $state ?? 'Không xác định',
                    })
                    ->sortable(),

                TextColumn::make('province.name')
                    ->label('Tỉnh/Thành phố')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('address')
                    ->label('Địa chỉ')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('hotline_phone')
                    ->label('Số điện thoại')
                    ->searchable(),

                TextColumn::make('priority_order')
                    ->label('Ưu tiên')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Loại điểm bàn giao')
                    ->options([
                        'POLICE_STATION' => 'Công an',
                        'COMMUNE_PEOPLE_COMMITTEE' => 'UBND xã/phường',
                        'HOTLINE_111' => 'Tổng đài Quốc gia 111',
                        'NGO' => 'Tổ chức NGO',
                        'OTHER' => 'Đơn vị khác',
                    ]),

                SelectFilter::make('province_id')
                    ->label('Tỉnh/Thành phố')
                    ->relationship('province', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('is_active')
                    ->label('Trạng thái')
                    ->options([
                        1 => 'Đang hoạt động',
                        0 => 'Ngừng hoạt động',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority_order');
    }
}
