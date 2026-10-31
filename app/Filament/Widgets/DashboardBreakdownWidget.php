<?php

namespace App\Filament\Widgets;

use App\Services\DashboardBreakdownService;
use Filament\Widgets\Widget;

class DashboardBreakdownWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-breakdown';

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $service = app(DashboardBreakdownService::class);

        return [
            'sectors' => $service->sectors(),
            'funnel' => $service->funnel(),
            'smallSample' => DashboardBreakdownService::SMALL_SAMPLE,
        ];
    }
}
