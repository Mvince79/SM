<?php

namespace App\Filament\Admin\Resources\HandoverPoints\Pages;

use App\Filament\Admin\Resources\HandoverPoints\HandoverPointResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHandoverPoint extends ViewRecord
{
    protected static string $resource = HandoverPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Chỉnh sửa'),
        ];
    }
}
