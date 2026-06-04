<?php

namespace App\Filament\Admin\Resources\MissingChildren\RelationManagers;

use App\Events\CommentHidden;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'allComments';

    protected static ?string $title = 'Bình luận';

    protected static ?string $recordTitleAttribute = 'body';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->whereNull('parent_id')
                    ->with(['user'])
                    ->latest()
            )
            ->columns([
                TextColumn::make('user.full_name')
                    ->label('Người dùng')
                    ->default('Khách')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('body')
                    ->label('Nội dung')
                    ->limit(80)
                    ->tooltip(fn($record) => $record->body)
                    ->searchable(),

                TextColumn::make('replies_count')
                    ->label('Replies')
                    ->counts('replies')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('is_hidden')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn($state) => (int) $state === 1 ? 'Ẩn' : 'Hiển thị')
                    ->badge()
                    ->color(fn($state) => (int) $state === 1 ? 'danger' : 'success'),

                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_hide')
                        ->label('Ẩn nhiều')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Ẩn các bình luận đã chọn?')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_hidden' => 1]);
                                broadcast(new CommentHidden($record->refresh()));
                            });
                            Notification::make()
                                ->title("Đã ẩn {$records->count()} bình luận")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->label('Xóa')
                        ->modalHeading('Xóa các bình luận đã chọn?')
                        ->modalDescription('Các bình luận sẽ bị xóa mềm (soft delete) và không thể khôi phục từ đây.'),
                ]),
            ])
            ->recordActions([
                Action::make('view_replies')
                    ->label('Xem replies')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Replies của: "' . Str::limit($record->body, 60) . '"')
                    ->modalContent(fn($record) => view(
                        'filament.modals.comment-replies',
                        ['comment' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->visible(fn($record) => $record->replies()->exists()),

                Action::make('hide')
                    ->label('Ẩn')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Ẩn bình luận?')
                    ->modalDescription('Bình luận sẽ bị ẩn khỏi giao diện người dùng.')
                    ->action(function ($record) {
                        $record->update(['is_hidden' => 1]);
                        broadcast(new CommentHidden($record->refresh()));
                        Notification::make()
                            ->title('Đã ẩn bình luận')
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => (int) $record->is_hidden === 0),

                Action::make('show')
                    ->label('Hiện')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->action(function ($record) {
                        $record->update(['is_hidden' => 0]);
                        broadcast(new CommentHidden($record->refresh()));
                        Notification::make()
                            ->title('Đã hiển thị bình luận')
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => (int) $record->is_hidden === 1),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('1s');
    }
}