<?php

namespace App\Filament\Admin\Resources\FaceMatch\Pages;

use App\Filament\Admin\Resources\FaceMatch\FaceMatchResource;
use Filament\Resources\Pages\EditRecord;

class EditFaceMatch extends EditRecord
{
    protected static string $resource = FaceMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Readonly resource — không cho edit
        ];
    }
}