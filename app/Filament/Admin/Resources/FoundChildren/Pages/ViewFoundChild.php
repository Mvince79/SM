<?php

namespace App\Filament\Admin\Resources\FoundChildren\Pages;

use App\Filament\Admin\Resources\FoundChildren\FoundChildResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFoundChild extends ViewRecord
{
    protected static string $resource = FoundChildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
