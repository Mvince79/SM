<?php

namespace App\Filament\Admin\Resources\MissingChildren\Pages;

use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMissingChild extends EditRecord
{
    protected static string $resource = MissingChildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
