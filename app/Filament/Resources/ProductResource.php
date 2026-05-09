<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    public static function form(Schema $schema): Schema
    {
        return $schema
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
                        Repeater::make('gallery')
                            ->schema([
                                TextInput::make('type')
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('image or video'),
                                FileUpload::make('url')
                                    ->label('File')
                                    ->disk('r2')
                                    ->directory('products/gallery')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/*', 'video/mp4'])
                                    ->maxSize(20480),
                                FileUpload::make('thumbnail')
                                    ->label('Video Thumbnail')
                                    ->disk('r2')
                                    ->directory('products/gallery/thumbnails')
                                    ->visibility('public')
                                    ->image()
                                    ->maxSize(4096),
                            ])
                            ->default([])
                            ->collapsible(),
                    ]),

                Section::make('Variants')
                    ->schema([
                        Repeater::make('magnification')
                            ->schema([
                                TextInput::make('id')->required()->maxLength(50),
                                TextInput::make('label')->required()->maxLength(100),
                                Toggle::make('available')->default(true),
                            ])
                            ->default([])
                            ->columns(3)
                            ->collapsible(),
                        Repeater::make('frame_colors')
                            ->schema([
                                TextInput::make('id')->required()->maxLength(50),
                                TextInput::make('name')->required()->maxLength(100),
                                TextInput::make('hex')->maxLength(10),
                                Toggle::make('available')->default(true),
                            ])
                            ->default([])
                            ->columns(4)
                            ->collapsible(),
                    ]),

                Section::make('Features')
                    ->schema([
                        Repeater::make('features')
                            ->schema([
                                TextInput::make('icon')->maxLength(100),
                                TextInput::make('title')->required()->maxLength(100),
                                TextInput::make('description')->required()->maxLength(2000),
                            ])
                            ->default([])
                            ->columns(3)
                            ->collapsible(),
                    ]),

                Section::make('Technical Specifications')
                    ->schema([
                        Repeater::make('technical_specifications')
                            ->schema([
                                TextInput::make('parameter')->required()->maxLength(150),
                                TextInput::make('specification')->required()->maxLength(500),
                            ])
                            ->default([])
                            ->columns(2)
                            ->collapsible(),
                    ]),

                Section::make('Trust Badges')
                    ->schema([
                        TagsInput::make('trust_badges')
                            ->label('Badges')
                            ->default([]),
                    ]),
            ]);
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
