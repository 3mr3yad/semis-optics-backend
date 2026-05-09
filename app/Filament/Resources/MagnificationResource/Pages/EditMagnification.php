<?php

namespace App\Filament\Resources\MagnificationResource\Pages;

use App\Filament\Resources\MagnificationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMagnification extends EditRecord
{
    protected static string $resource = MagnificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
