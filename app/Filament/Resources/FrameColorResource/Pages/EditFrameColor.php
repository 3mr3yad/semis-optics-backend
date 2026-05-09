<?php

namespace App\Filament\Resources\FrameColorResource\Pages;

use App\Filament\Resources\FrameColorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFrameColor extends EditRecord
{
    protected static string $resource = FrameColorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
