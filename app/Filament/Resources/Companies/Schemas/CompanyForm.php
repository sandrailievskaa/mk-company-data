<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\SectorEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Име на компанија')
                    ->prefixIcon(Heroicon::OutlinedBuildingOffice),
                Forms\Components\Select::make('sector')
                    ->options(SectorEnum::class)
                    ->label('Сектор')
                    ->prefixIcon(Heroicon::OutlinedBriefcase),
                Forms\Components\TextInput::make('city')
                    ->label('Град')
                    ->prefixIcon(Heroicon::OutlinedMapPin),
                Forms\Components\TextInput::make('address')
                    ->label('Адреса')
                    ->prefixIcon(Heroicon::OutlinedHome),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->label('Е-пошта')
                    ->prefixIcon(Heroicon::OutlinedEnvelope),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->label('Телефонски број')
                    ->prefixIcon(Heroicon::OutlinedPhone),
                Forms\Components\TextInput::make('activity_index')
                    ->label('Индекс на активност')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->prefixIcon(Heroicon::OutlinedChartBar)
                    ->helperText('Автоматски се ажурира врз основа на активност'),
            ]);
    }
}
