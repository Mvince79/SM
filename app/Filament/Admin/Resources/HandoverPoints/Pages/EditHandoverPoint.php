<?php

namespace App\Filament\Admin\Resources\HandoverPoints\Pages;

use App\Filament\Admin\Resources\HandoverPoints\HandoverPointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHandoverPoint extends EditRecord
{
    protected static string $resource = HandoverPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
