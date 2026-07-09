<?php

namespace App\Filament\Admin\Resources\KycVerifications\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KycVerificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.full_name')
                    ->label('Người dùng')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('full_name_on_id')
                    ->label('Tên trên CCCD')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('ocr_provider')
                    ->label('OCR')
                    ->badge()
                    ->placeholder('Chưa có'),

                TextColumn::make('face_match_score')
                    ->label('Điểm khớp mặt')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state * 100, 2) . '%' : 'Chưa có')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'PENDING' => 'warning',
                        'VERIFIED' => 'success',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'PENDING' => 'Chờ duyệt',
                        'VERIFIED' => 'Đã xác thực',
                        'REJECTED' => 'Từ chối',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ngày gửi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('verified_at')
                    ->label('Ngày duyệt')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Chưa duyệt')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'PENDING' => 'Chờ duyệt',
                        'VERIFIED' => 'Đã xác thực',
                        'REJECTED' => 'Từ chối',
                    ]),

                SelectFilter::make('ocr_provider')
                    ->label('OCR Provider')
                    ->options([
                        'fpt_ai' => 'FPT.AI',
                        'vnpt_ekyc' => 'VNPT eKYC',
                        'tesseract' => 'Tesseract',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Xem'),
            ])
            ->toolbarActions([]);
    }
}