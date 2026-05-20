<?php

namespace App\Filament\Admin\Resources\MissingChildren\Pages;

use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMissingChildren extends ListRecords
{
    protected static string $resource = MissingChildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
