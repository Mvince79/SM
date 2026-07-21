<?php

namespace App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\Pages;

use App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\ThirdPartyVerifierProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditThirdPartyVerifierProfile extends EditRecord
{
    protected static string $resource = ThirdPartyVerifierProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
