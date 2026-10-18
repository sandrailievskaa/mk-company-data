<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Offers\OfferResource;
use App\Services\DashboardStatsService;
use App\Services\Offers\CampaignStats;
use Filament\Widgets\Widget;

class DashboardOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-overview';

    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'stats' => app(DashboardStatsService::class)->build(),
            'newOfferUrl' => OfferResource::getUrl('create'),
            'campaign' => app(CampaignStats::class)->summary(),
        ];
    }
}
