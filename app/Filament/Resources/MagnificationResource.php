<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MagnificationResource\Pages;
use App\Models\Magnification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MagnificationResource extends Resource
{
    protected static ?string $model = Magnification::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Magnification')
                    ->schema([
                        TextInput::make('code')->required()->maxLength(50)->unique(ignoreRecord: true),
                        TextInput::make('label')->required()->maxLength(100),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('label')->searchable()->sortable(),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMagnifications::route('/'),
            'create' => Pages\CreateMagnification::route('/create'),
            'edit' => Pages\EditMagnification::route('/{record}/edit'),
        ];
    }
}
