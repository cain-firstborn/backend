<?php

namespace App\Filament\Resources\SignUpResource\Pages;

use App\Filament\Resources\SignUpResource;
use Filament\Resources\Pages\ListRecords;

class ListSignUps extends ListRecords
{
    protected static string $resource = SignUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
