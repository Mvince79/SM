<?php

namespace App\Filament\Admin\Resources\MissingChildren\RelationManagers;

use App\Models\MissingChild;
use App\Models\Photo;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';
    public function isReadOnly(): bool
    {
        return false;
    }
    protected static ?string $title = 'Ảnh';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path_original')
                    ->label('Ảnh')
                    ->disk('public')
                    ->directory(fn() => 'missing/' . $this->ownerRecord->id)
                    ->image()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path_original')
            ->columns([
                ImageColumn::make('photo_preview')
                    ->label('Ảnh')
                    ->state(function ($record) {
                        return $record->path_thumb
                            ?: $record->path_original
                            ?: $record->path_blurred;
                    })
                    ->disk('public')
                    ->height(120)
                    ->width(120),

                TextColumn::make('path_original')
                    ->label('Đường dẫn ảnh')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->path_original)
                    ->searchable(),

                TextColumn::make('is_primary')
                    ->label('Ảnh chính')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn($state) => $state ? 'Ảnh chính' : 'Ảnh phụ'),

                TextColumn::make('sort_order')
                    ->label('Thứ tự')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload ảnh')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['model_type'] = MissingChild::class;
                        $data['model_id'] = $this->ownerRecord->id;
                        $data['sort_order'] = Photo::where('model_type', MissingChild::class)
                            ->where('model_id', $this->ownerRecord->id)
                            ->max('sort_order') + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('set_primary')
                    ->label('Đặt làm ảnh chính')
                    ->icon('heroicon-o-star')
                    ->color('success')
                    ->visible(fn($record) => ! $record->is_primary)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        DB::transaction(function () use ($record) {
                            Photo::where('model_type', MissingChild::class)
                                ->where('model_id', $this->ownerRecord->id)
                                ->update(['is_primary' => 0]);

                            $record->update(['is_primary' => 1]);
                        });
                    }),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(
                fn($action) => $action->button()->label('Sắp xếp ảnh')
            )
            ->defaultSort('sort_order');
    }
}
