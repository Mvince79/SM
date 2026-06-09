<?php

namespace App\Filament\Admin\Resources\FaceMatchResults\Pages;

use App\Filament\Admin\Resources\FaceMatchResults\FaceMatchResultResource;
use Filament\Resources\Pages\EditRecord;

class EditFaceMatchResult extends EditRecord
{
    protected static string $resource = FaceMatchResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Readonly resource — không cho edit
        ];
    }
}
