<?php

namespace App\Filament\Admin\Resources\HandoverPoints\Pages;

use App\Filament\Admin\Resources\HandoverPoints\HandoverPointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHandoverPoints extends ListRecords
{
    protected static string $resource = HandoverPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
