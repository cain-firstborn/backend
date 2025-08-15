<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    /**
     * The resource class that this page corresponds to.
     *
     * @var string
     */
    protected static string $resource = UserResource::class;

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
