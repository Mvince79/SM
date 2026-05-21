<?php

namespace App\Filament\Admin\Resources\FoundChildren\Pages;

use App\Filament\Admin\Resources\FoundChildren\FoundChildResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFoundChild extends CreateRecord
{
    protected static string $resource = FoundChildResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
