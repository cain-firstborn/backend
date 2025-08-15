<?php

namespace App\Filament\Resources\SignUpResource\Pages;

use App\Filament\Resources\SignUpResource;
use Filament\Resources\Pages\ListRecords;

class ListSignUps extends ListRecords
{
    /**
     * The resource class that this page corresponds to.
     *
     * @var string
     */
    protected static string $resource = SignUpResource::class;

    /**
     * Get the header actions for the page.
     *
     * @return array
     */
    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
