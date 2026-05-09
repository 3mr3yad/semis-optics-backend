<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\FrameColor;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductFrameColorsRelationManager extends RelationManager
{
    protected static string $relationship = 'frameColors';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Toggle::make('pivot.available')
                    ->label('Available')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('hex'),
                IconColumn::make('pivot.available')->boolean()->label('Available'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectOptionsQuery(fn ($query) => $query->where('is_active', true))
                    ->recordTitleAttribute('name')
                    ->form([
                        Toggle::make('available')->default(true),
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        Toggle::make('pivot.available')->label('Available'),
                    ]),
                DetachAction::make(),
            ]);
    }
}
