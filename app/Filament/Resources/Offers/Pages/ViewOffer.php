<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Filament\Resources\Offers\OfferResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOffer extends ViewRecord
{
    protected static string $resource = OfferResource::class;

    public function getPageClasses(): array
    {
        return array_merge(parent::getPageClasses(), [
            'mk-offer-view',
        ]);
    }

    public function getTitle(): string
    {
        return (string) $this->getRecord()->title;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Преглед';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Уреди')
                ->icon(Heroicon::OutlinedPencilSquare),
        ];
    }
}
