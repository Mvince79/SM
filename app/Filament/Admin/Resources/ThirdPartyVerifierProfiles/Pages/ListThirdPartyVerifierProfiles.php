<?php

namespace App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\Pages;

use App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\ThirdPartyVerifierProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThirdPartyVerifierProfiles extends ListRecords
{
    protected static string $resource = ThirdPartyVerifierProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
