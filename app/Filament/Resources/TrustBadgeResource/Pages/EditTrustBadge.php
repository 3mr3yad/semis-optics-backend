<?php

namespace App\Filament\Resources\TrustBadgeResource\Pages;

use App\Filament\Resources\TrustBadgeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrustBadge extends EditRecord
{
    protected static string $resource = TrustBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
