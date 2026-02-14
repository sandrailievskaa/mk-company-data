<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Offer;
use App\Models\SentOffer;
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

            Stat::make('Активни понуди', Offer::where('is_active', true)->count())
                ->description('Понуди подготвени за испраќање')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Испратени понуди', SentOffer::count())
                ->description('Вкупно испратени понуди')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('success'),
        ];
    }
}
