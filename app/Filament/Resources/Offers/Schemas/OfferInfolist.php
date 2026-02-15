<?php

namespace App\Filament\Resources\Offers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OfferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Наслов')
                    ->extraAttributes(['style' => 'white-space: nowrap; overflow: hidden; text-overflow: ellipsis;']),
                TextEntry::make('content')
                    ->label('Содржина')
                    ->columnSpanFull()
                    ->html()
                    ->formatStateUsing(fn ($state) => nl2br(e($state))),
                TextEntry::make('company.name')
                    ->label('Име на компанија'),
                TextEntry::make('created_at')
                    ->label('Креирано на')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Ажурирано на')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
