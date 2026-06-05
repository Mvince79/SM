<?php

namespace App\Filament\Admin\Resources\FaceMatchResults\Pages;

use App\Filament\Admin\Resources\FaceMatchResults\FaceMatchResultResource;
use Filament\Resources\Pages\ListRecords;

class ListFaceMatchResults extends ListRecords
{
    protected static string $resource = FaceMatchResultResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}