<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
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
            Actions\DeleteAction::make(),
        ];
    }
}
