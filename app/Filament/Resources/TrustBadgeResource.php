<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrustBadgeResource\Pages;
use App\Models\TrustBadge;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrustBadgeResource extends Resource
{
    protected static ?string $model = TrustBadge::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Form $form): Form
    {
        return $form
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
