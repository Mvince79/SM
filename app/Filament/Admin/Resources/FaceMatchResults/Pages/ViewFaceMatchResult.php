<?php

namespace App\Filament\Admin\Resources\FaceMatchResults\Pages;

use App\Filament\Admin\Resources\FaceMatchResults\FaceMatchResultResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFaceMatchResult extends ViewRecord
{
    protected static string $resource = FaceMatchResultResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
