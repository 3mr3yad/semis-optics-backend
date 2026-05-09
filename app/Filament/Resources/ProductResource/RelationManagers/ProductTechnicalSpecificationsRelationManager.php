<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductTechnicalSpecificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'technicalSpecifications';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('parameter')->required()->maxLength(150),
                TextInput::make('specification')->required()->maxLength(500),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parameter')->searchable(),
                TextColumn::make('specification')->limit(50),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order');
    }
}
