<?php

namespace App\Filament\Resources\Offers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship(name: 'company', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('additional_information')
                    ->label('Additional Information')
                    ->helperText('Provide additional information about the offer. This will be used to generate the offer content.')
                    ->rows(5)
                    ->columnSpanFull()
            ]);
    }
}
