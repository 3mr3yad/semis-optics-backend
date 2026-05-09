<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                        TextInput::make('badge')->maxLength(255),
                        TextInput::make('price')->required()->numeric()->minValue(0),
                        TextInput::make('currency')->required()->default('USD')->length(3),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2),

                Section::make('Rating')
                    ->schema([
                        TextInput::make('rating_score')->required()->numeric()->minValue(0)->maxValue(5),
                        TextInput::make('total_reviews')->required()->numeric()->integer()->minValue(0),
                    ])
                    ->columns(2),

                Section::make('Media')
                    ->schema([
                        FileUpload::make('main_image')
                            ->label('Main Image')
                            ->disk('r2')
                            ->directory('products/main')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxSize(4096),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductResource\RelationManagers\ProductMediaRelationManager::class,
            ProductResource\RelationManagers\ProductMagnificationsRelationManager::class,
            ProductResource\RelationManagers\ProductFrameColorsRelationManager::class,
            ProductResource\RelationManagers\ProductFeaturesRelationManager::class,
            ProductResource\RelationManagers\ProductTechnicalSpecificationsRelationManager::class,
            ProductResource\RelationManagers\ProductTrustBadgesRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('price')->money('USD')->sortable(),
                TextColumn::make('rating_score')->label('Rating')->sortable(),
                TextColumn::make('total_reviews')->label('Reviews')->sortable(),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
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
