<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Companies\CompanyResource;
use App\Services\DashboardActivityInsightsService;
use Filament\Widgets\Widget;

class DashboardInsightsRowWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-insights-row';

    protected static ?int $sort = 0;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $svc = app(DashboardActivityInsightsService::class);
        $dist = $svc->getActivityIndexDistribution();

        return [
            'distribution' => $dist,
            'subtitle' => $svc->getActivityChartSubtitle(),
            'companies' => $svc->getTopByActivityIndex(5),
            'allUrl' => CompanyResource::getUrl('index'),
        ];
    }
}
