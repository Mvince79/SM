<?php

namespace App\Filament\Admin\Resources\MissingChildren\Pages;

use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMissingChild extends CreateRecord
{
    protected static string $resource = MissingChildResource::class;
}
