<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrustBadgeResource\Pages;
use App\Models\TrustBadge;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrustBadgeResource extends Resource
{
    protected static ?string $model = TrustBadge::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Trust Badge')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(100)->unique(ignoreRecord: true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrustBadges::route('/'),
            'create' => Pages\CreateTrustBadge::route('/create'),
            'edit' => Pages\EditTrustBadge::route('/{record}/edit'),
        ];
    }
}
