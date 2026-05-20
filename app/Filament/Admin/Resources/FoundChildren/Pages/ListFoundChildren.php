<?php

namespace App\Filament\Admin\Resources\FoundChildren\Pages;

use App\Filament\Admin\Resources\FoundChildren\FoundChildResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFoundChildren extends ListRecords
{
    protected static string $resource = FoundChildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
