<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductTrustBadgesRelationManager extends RelationManager
{
    protected static string $relationship = 'trustBadges';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
            ])
            ->headerActions([
                AttachAction::make()->recordTitleAttribute('name'),
            ])
            ->actions([
                DetachAction::make(),
            ]);
    }
}
