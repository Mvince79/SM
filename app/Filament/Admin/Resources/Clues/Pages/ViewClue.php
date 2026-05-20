<?php

namespace App\Filament\Admin\Resources\Clues\Pages;

use App\Filament\Admin\Resources\Clues\ClueResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClue extends ViewRecord
{
    protected static string $resource = ClueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
