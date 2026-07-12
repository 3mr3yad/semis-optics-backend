<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    private array $colorImages = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['colorImages'] = $this->record->colors->map(fn ($color): array => [
            'color_id' => $color->id,
            'image' => $color->pivot->image,
        ])->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['image']) && is_array($data['image'])) {
            $data['image'] = reset($data['image']) ?: null;
        }

        $this->colorImages = $data['colorImages'] ?? [];
        unset($data['colorImages']);

        return $data;
    }

    protected function afterSave(): void
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
