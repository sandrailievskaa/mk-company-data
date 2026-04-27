<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Filament\Resources\Offers\OfferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;

class ListOffers extends ListRecords
{
    protected static string $resource = OfferResource::class;

    protected static ?string $title = 'Понуди';

    public function getPageClasses(): array
    {
        return array_merge(parent::getPageClasses(), [
            'mk-offers-list',
        ]);
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Понуди';
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\Offer>
     */
    public function getPaginatedOffers()
    {
        return static::getResource()::getEloquentQuery()
            ->with('company')
            ->latest('created_at')
            ->paginate(9, ['*'], 'offersPage');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Нова понуда')
                ->icon(Heroicon::OutlinedPlus)
                ->color('gray')
                ->outlined(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                SchemaView::make('filament.resources.offers.list-offers-grid'),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }
}
