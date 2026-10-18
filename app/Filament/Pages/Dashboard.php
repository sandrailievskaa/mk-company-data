<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardInsightsRowWidget;
use App\Filament\Widgets\DashboardOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected Width|string|null $maxContentWidth = Width::Full;

    /**
     * Еден ред по widget: горе картички (цел main), подолу графикон + препораки (внатрешна 2+1 мрежа).
     */
    public function getColumns(): int|array
    {
        return 1;
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            DashboardOverviewWidget::class,
            DashboardInsightsRowWidget::class,
        ];
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public static function getNavigationLabel(): string
    {
        return __('Контролна табла');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('Контролна табла');
    }
}
