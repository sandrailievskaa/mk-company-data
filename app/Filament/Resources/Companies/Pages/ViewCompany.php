<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    public function getPageClasses(): array
    {
        return array_merge(parent::getPageClasses(), [
            'mk-offer-view',
            'mk-company-view',
        ]);
    }

    public function getTitle(): string
    {
        return (string) $this->getRecord()->name;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Преглед';
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->getRecord()->loadCount('offers');
    }

    /**
     * @return array<\Filament\Actions\Action | \Filament\Actions\ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Уреди')
                ->icon(Heroicon::OutlinedPencilSquare),
        ];
    }
}
