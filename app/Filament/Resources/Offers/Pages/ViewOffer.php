<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Filament\Resources\Offers\OfferResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOffer extends ViewRecord
{
    protected static string $resource = OfferResource::class;

    public function getTitle(): string
    {
        return 'Детали';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Уреди'),
        ];
    }
}
