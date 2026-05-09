<?php

namespace App\Filament\Resources\MagnificationResource\Pages;

use App\Filament\Resources\MagnificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMagnifications extends ListRecords
{
    protected static string $resource = MagnificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
