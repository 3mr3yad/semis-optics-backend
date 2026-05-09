<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrameColorResource\Pages;
use App\Models\FrameColor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as ComponentsSection;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FrameColorResource extends Resource
{
    protected static ?string $model = FrameColor::class;

    protected static string $navigationIcon = 'heroicon-o-swatch';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ComponentsSection::make('Frame Color')
                    ->schema([
                        TextInput::make('code')->required()->maxLength(50)->unique(ignoreRecord: true),
                        TextInput::make('name')->required()->maxLength(100),
                        TextInput::make('hex')->maxLength(10),
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
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('hex'),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFrameColors::route('/'),
            'create' => Pages\CreateFrameColor::route('/create'),
            'edit' => Pages\EditFrameColor::route('/{record}/edit'),
        ];
    }
}
