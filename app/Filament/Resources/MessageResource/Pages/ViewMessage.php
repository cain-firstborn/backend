<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMessage extends ViewRecord
{
    /**
     * The resource class that this page corresponds to.
     *
     * @var string
     */
    protected static string $resource = MessageResource::class;

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
