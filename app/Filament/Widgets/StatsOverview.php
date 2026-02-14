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
            Stat::make('Вкупно компании', Company::count())
                ->description('Сите регистрирани компании')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Компании со email', Company::whereNotNull('email')->count())
                ->description('Компании со валиден email')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('info'),
        ];
    }
}
