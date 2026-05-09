<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductMediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('type')
                    ->required()
                    ->maxLength(20),
                FileUpload::make('url')
                    ->label('File')
                    ->disk('r2')
                    ->directory('products/gallery')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/*', 'video/mp4'])
                    ->maxSize(20480)
                    ->required(),
                FileUpload::make('thumbnail')
                    ->label('Video Thumbnail')
                    ->disk('r2')
                    ->directory('products/gallery/thumbnails')
                    ->visibility('public')
                    ->image()
                    ->maxSize(4096),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->sortable(),
                TextColumn::make('url')->limit(50),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order');
    }
}
