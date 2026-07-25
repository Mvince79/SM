<?php

namespace App\Filament\Admin\Resources\SupportTickets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';

    protected static ?string $title = 'Phản hồi và ghi chú';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('message')
                    ->label('Nội dung')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),

                Toggle::make('is_internal_note')
                    ->label('Ghi chú nội bộ')
                    ->helperText(
                        'Bật nếu nội dung này chỉ dành cho admin. Người gửi ticket sẽ không nhìn thấy.'
                    )
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.full_name')
                    ->label('Người trả lời')
                    ->placeholder('Không xác định')
                    ->searchable(),

                TextColumn::make('message')
                    ->label('Nội dung')
                    ->wrap()
                    ->limit(100)
                    ->searchable(),

                IconColumn::make('is_internal_note')
                    ->label('Nội bộ')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-globe-alt'),

                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Thêm phản hồi')
                    ->modalHeading('Thêm phản hồi hoặc ghi chú')
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Sửa')
                    ->visible(fn (Model $record): bool => $record->user_id === auth()->id()),

                DeleteAction::make()
                    ->label('Xóa')
                    ->visible(fn (Model $record): bool => $record->user_id === auth()->id()),
            ])
            ->emptyStateHeading('Chưa có phản hồi')
            ->emptyStateDescription(
                'Admin có thể thêm phản hồi công khai hoặc ghi chú nội bộ cho ticket.'
            );
    }
}