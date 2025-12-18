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
                Forms\Components\TextInput::make('name')->required()->label('Company Name'),
                Forms\Components\TextInput::make('sector')->label('Sector'),
                Forms\Components\TextInput::make('city')->label('City'),
                Forms\Components\TextInput::make('email')->email()->label('E-mail'),
            ]);
    }
}
