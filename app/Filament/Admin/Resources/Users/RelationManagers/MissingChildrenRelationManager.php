<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MissingChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'missingChildren';

    protected static ?string $title = 'Tin đăng';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('case_code')
                    ->label('Mã hồ sơ')
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Tên trẻ')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        'FOUND' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Ngày đăng')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}