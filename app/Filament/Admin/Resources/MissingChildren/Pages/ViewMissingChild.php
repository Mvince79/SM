<?php

namespace App\Filament\Admin\Resources\MissingChildren\Pages;

use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMissingChild extends ViewRecord
{
    protected static string $resource = MissingChildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
