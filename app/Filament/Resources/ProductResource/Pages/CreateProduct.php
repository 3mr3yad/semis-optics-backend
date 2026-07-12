<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    private array $colorImages = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['image']) && is_array($data['image'])) {
            $data['image'] = reset($data['image']) ?: null;
        }

        $this->colorImages = $data['colorImages'] ?? [];
        unset($data['colorImages']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $pivotData = collect($this->colorImages)
            ->filter(fn (array $color): bool => filled($color['color_id'] ?? null))
            ->mapWithKeys(fn (array $color): array => [
                $color['color_id'] => ['image' => $color['image'] ?? null],
            ])
            ->all();

        $this->record->colors()->sync($pivotData);
    }
}
