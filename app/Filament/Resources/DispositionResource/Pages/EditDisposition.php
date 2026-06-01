<?php

namespace App\Filament\Resources\DispositionResource\Pages;

use App\Filament\Resources\DispositionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDisposition extends EditRecord
{
    protected static string $resource = DispositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
