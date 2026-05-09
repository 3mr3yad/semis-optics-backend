<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductFeaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'features';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                FileUpload::make('icon')
                    ->disk('r2')
                    ->directory('products/features/icons')
                    ->visibility('public')
                    ->image()
                    ->maxSize(2048),
                TextInput::make('title')->required()->maxLength(100),
                TextInput::make('description')->required()->maxLength(2000),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order');
    }
}
