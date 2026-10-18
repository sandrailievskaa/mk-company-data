<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('Вкупно компании'), Company::count())
                ->description(__('Сите регистрирани компании'))
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make(__('Компании со е-пошта'), Company::whereNotNull('email')->count())
                ->description(__('Компании со валидна е-пошта'))
                ->descriptionIcon('heroicon-m-envelope')
                ->color('info'),
        ];
    }
}
