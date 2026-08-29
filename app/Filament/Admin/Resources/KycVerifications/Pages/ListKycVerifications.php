<?php

namespace App\Filament\Admin\Resources\KycVerifications\Pages;

use App\Filament\Admin\Resources\KycVerifications\KycVerificationResource;
use Filament\Resources\Pages\ListRecords;

class ListKycVerifications extends ListRecords
{
    protected static string $resource = KycVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
