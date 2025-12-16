<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('name')->required()->label('Име на фирма'),
                Forms\Components\TextInput::make('sector')->label('Сектор'),
                Forms\Components\TextInput::make('city')->label('Град'),
                Forms\Components\TextInput::make('email')->email()->label('Е-маил'),
            ]);
    }
}
