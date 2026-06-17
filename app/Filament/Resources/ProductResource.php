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

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->nullable(),

                Forms\Components\TextInput::make('price')
                    ->label('Price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01),

                Forms\Components\TextInput::make('price_after_discount')
                    ->label('Price After Discount')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->nullable(),

                Forms\Components\FileUpload::make('image')
                    ->label('Image')
                    ->disk(static::uploadDisk())
                    ->directory('products')
                    ->image()
                    ->visibility('public')
                    ->maxSize(5120)
                    ->nullable(),

                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),

                Forms\Components\CheckboxList::make('colors')
                    ->relationship('colors', 'name')
                    ->searchable()
                    ->bulkToggleable(),
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

                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_after_discount')
                    ->label('Discount Price')
                    ->money()
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
