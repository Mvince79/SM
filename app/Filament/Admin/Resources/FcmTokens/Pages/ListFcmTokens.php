<?php

namespace App\Filament\Admin\Resources\FcmTokens\Pages;

use App\Filament\Admin\Resources\FcmTokens\FcmTokensResource;
use Filament\Resources\Pages\ListRecords;

class ListFcmTokens extends ListRecords
{
    protected static string $resource = FcmTokensResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
