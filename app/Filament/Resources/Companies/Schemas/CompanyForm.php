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
                    ->label(__('Име на компанија'))
                    ->prefixIcon(Heroicon::OutlinedBuildingOffice),
                Forms\Components\Select::make('sector')
                    ->options(SectorEnum::class)
                    ->label(__('Сектор'))
                    ->prefixIcon(Heroicon::OutlinedBriefcase),
                Forms\Components\TextInput::make('city')
                    ->label(__('Град'))
                    ->prefixIcon(Heroicon::OutlinedMapPin),
                Forms\Components\TextInput::make('address')
                    ->label(__('Адреса'))
                    ->prefixIcon(Heroicon::OutlinedHome),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->label(__('Е-пошта'))
                    ->prefixIcon(Heroicon::OutlinedEnvelope),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->label(__('Телефонски број'))
                    ->prefixIcon(Heroicon::OutlinedPhone),
                Forms\Components\TextInput::make('activity_index')
                    ->label(__('Индекс на активност'))
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->prefixIcon(Heroicon::OutlinedChartBar)
                    ->helperText(__('Автоматски се ажурира врз основа на активност')),
            ]);
    }
}
