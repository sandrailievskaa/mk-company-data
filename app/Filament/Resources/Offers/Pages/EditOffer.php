<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Filament\Resources\Offers\OfferResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditOffer extends EditRecord
{
    protected static string $resource = OfferResource::class;

    protected Width|string|null $maxContentWidth = '720px';

    public function getPageClasses(): array
    {
        return array_merge(parent::getPageClasses(), [
            'mk-offer-edit',
        ]);
    }

    public function getTitle(): string
    {
        return 'Уреди понуда';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Преглед'),
            DeleteAction::make()
                ->label('Избриши'),
        ];
    }
}
