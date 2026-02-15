<?php

namespace App\Filament\Resources\Offers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Компанија')
                    ->relationship(name: 'company', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->prefixIcon(Heroicon::OutlinedBuildingOffice),
                Textarea::make('additional_information')
                    ->label('Дополнителни информации')
                    ->helperText('Внесете дополнителни информации за понудата. Ова ќе се користи за генерирање на содржината на понудата.')
                    ->rows(5)
                    ->columnSpanFull()
            ]);
    }
}
