<?php

namespace App\Filament\Admin\Resources\MissingChildren\Schemas;

use Filament\Schemas\Schema;

class MissingChildForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
