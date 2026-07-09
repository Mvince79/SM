<?php

namespace App\Filament\Admin\Resources\KycVerifications\Pages;

use App\Filament\Admin\Resources\KycVerifications\KycVerificationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditKycVerification extends EditRecord
{
    protected static string $resource = KycVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
