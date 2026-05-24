<?php

namespace App\Filament\Admin\Resources\FoundChildResource\Pages;

use App\Filament\Admin\Resources\FoundChildResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFoundChild extends CreateRecord
{
    protected static string $resource = FoundChildResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}