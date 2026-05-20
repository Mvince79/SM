<?php

namespace App\Filament\Admin\Resources\Clues\Pages;

use App\Filament\Admin\Resources\Clues\ClueResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClue extends EditRecord
{
    protected static string $resource = ClueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
