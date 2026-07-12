<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    protected static UnitEnum|string|null $navigationGroup = 'Catalog';

    private static function uploadDisk(): string
    {
        return filled(config('filesystems.disks.r2.key'))
            && filled(config('filesystems.disks.r2.secret'))
            && filled(config('filesystems.disks.r2.bucket'))
            && filled(config('filesystems.disks.r2.endpoint'))
                ? 'r2'
                : 'public';
    }

    private static function normalizeModelData(array $data): array
    {
        if (isset($data['image']) && is_array($data['image'])) {
            $data['image'] = reset($data['image']) ?: null;
        }

        $data['attributes'] = $data['attributes'] ?? [];
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('image')
                    ->label('Image')
                    ->disk(static::uploadDisk())
                    ->directory('products')
                    ->image()
                    ->visibility('public')
                    ->maxSize(5120)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('colorImages')
                    ->label('Colors')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->addActionLabel('Add Color')
                    ->itemLabel(fn (array $state): ?string => \App\Models\Color::find($state['color_id'] ?? null)?->name)
                    ->schema([
                        Forms\Components\Select::make('color_id')
                            ->label('Color')
                            ->options(fn () => \App\Models\Color::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\FileUpload::make('image')
                            ->label('Image')
                            ->disk(static::uploadDisk())
                            ->directory('product-colors')
                            ->image()
                            ->visibility('public')
                            ->maxSize(5120)
                            ->nullable(),
                    ]),

                Forms\Components\Repeater::make('models')
                    ->relationship('models')
                    ->label('Models')
                    ->columnSpanFull()
                    ->columns(2)
                    ->defaultItems(1)
                    ->collapsible()
                    ->addActionLabel('Add Model')
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New model')
                    ->addAction(function (\Filament\Actions\Action $action): \Filament\Actions\Action {
                        return $action->action(function (Forms\Components\Repeater $component): void {
                            $items = $component->getRawState();

                            $lastAttributes = collect($items)->last()['attributes'] ?? [];

                            $newUuid = $component->generateUuid();

                            $newItem = [
                                'attributes' => is_array($lastAttributes)
                                    ? array_fill_keys(array_keys($lastAttributes), '')
                                    : [],
                            ];

                            if ($newUuid) {
                                $items[$newUuid] = $newItem;
                            } else {
                                $items[] = $newItem;
                            }

                            $component->rawState($items);

                            $component->getChildSchema($newUuid ?? array_key_last($items))->fill();

                            $component->collapsed(false, shouldMakeComponentCollapsible: false);

                            $component->callAfterStateUpdated();

                            $component->shouldPartiallyRenderAfterActionsCalled() ? $component->partiallyRender() : null;
                        });
                    })
                    ->extraItemActions([
                        fn (Forms\Components\Repeater $component): \Filament\Actions\Action => \Filament\Actions\Action::make('copyAttributeNames')
                            ->label('Copy attribute names to other models')
                            ->icon('heroicon-o-document-duplicate')
                            ->action(function (array $arguments) use ($component): void {
                                $items = $component->getRawState();

                                $sourceAttributes = $items[$arguments['item']]['attributes'] ?? [];

                                if (empty($sourceAttributes)) {
                                    return;
                                }

                                $keys = is_array($sourceAttributes)
                                    ? array_fill_keys(array_keys($sourceAttributes), '')
                                    : [];

                                foreach ($items as $uuid => $item) {
                                    if ($uuid === $arguments['item']) {
                                        continue;
                                    }

                                    $items[$uuid]['attributes'] = $keys + ($item['attributes'] ?? []);
                                }

                                $component->rawState($items);
                                $component->callAfterStateUpdated();
                                $component->shouldPartiallyRenderAfterActionsCalled() ? $component->partiallyRender() : null;
                            }),
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->nullable(),

                        Forms\Components\TextInput::make('price_after_discount')
                            ->label('Price After Discount')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->nullable(),

                        Forms\Components\FileUpload::make('image')
                            ->label('Image')
                            ->disk(static::uploadDisk())
                            ->directory('product-models')
                            ->image()
                            ->visibility('public')
                            ->maxSize(5120)
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\KeyValue::make('attributes')
                            ->keyLabel('Attribute Name')
                            ->valueLabel('Attribute Value')
                            ->addActionLabel('Add Attribute')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => static::normalizeModelData($data))
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => static::normalizeModelData($data)),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('colors'))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\ImageColumn::make('image')
                    ->circular()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $image = $record->image;
                        if (empty($image)) {
                            return null;
                        }
                        if (!str_starts_with($image, 'http://') && !str_starts_with($image, 'https://')) {
                            return app(\App\Services\CloudflareR2Service::class)->url($image);
                        }
                        return $image;
                    }),

                Tables\Columns\TextColumn::make('colors')
                    ->label('Colors')
                    ->formatStateUsing(function ($record) {
                        $colors = $record->colors;
                        if ($colors->isEmpty()) {
                            return '-';
                        }
                        return $colors
                            ->unique('id')
                            ->map(function ($color) {
                                $hex = $color->hex_code ?? '#000000';
                                return "<div style='width: 20px; height: 20px; background-color: {$hex}; border-radius: 50%; display: inline-block; margin-right: 4px; border: 1px solid #ccc;' title='{$hex}'></div>";
                            })
                            ->implode('');
                    })
                    ->html()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
