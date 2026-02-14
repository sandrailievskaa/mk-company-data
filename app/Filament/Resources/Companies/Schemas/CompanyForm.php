<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\SectorEnum;
use Filament\Forms;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('name')->required()->label('Име на компанија'),
                Forms\Components\Select::make('sector')->options(SectorEnum::class)->label('Сектор'),
                Forms\Components\TextInput::make('city')->label('Град'),
                Forms\Components\TextInput::make('address')->label('Адреса'),
                Forms\Components\TextInput::make('email')->email()->label('Е-пошта'),
                Forms\Components\TextInput::make('phone')->tel()->label('Телефонски број'),
                Forms\Components\TextInput::make('activity_index')
                    ->label('Индекс на активност')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->helperText('Автоматски се ажурира врз основа на активност'),
            ]);
    }
}
