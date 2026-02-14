<?php

namespace App\Filament\Resources\Offers\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->label('Наслов')
                    ->maxLength(255),
                Forms\Components\TextInput::make('subject')
                    ->label('Предмет')
                    ->maxLength(255),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->label('Содржина')
                    ->rows(10)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),
            ]);
    }
}
