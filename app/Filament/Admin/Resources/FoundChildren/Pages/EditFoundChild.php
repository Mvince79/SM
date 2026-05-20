<?php

namespace App\Filament\Admin\Resources\FoundChildren\Pages;

use App\Filament\Admin\Resources\FoundChildren\FoundChildResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFoundChild extends EditRecord
{
    protected static string $resource = FoundChildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
