<?php

namespace App\Filament\Resources\DispositionResource\Pages;

use App\Filament\Resources\DispositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDispositions extends ListRecords
{
    protected static string $resource = DispositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
