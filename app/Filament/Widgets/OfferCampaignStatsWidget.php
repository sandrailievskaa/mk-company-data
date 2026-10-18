<?php

namespace App\Filament\Widgets;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Models\SentOffer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OfferCampaignStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Понуди и испраќања';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $counts = SentOffer::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $count = fn (OfferStatus $s): int => (int) ($counts[$s->value] ?? 0);

        $delivered = $count(OfferStatus::Sent) + $count(OfferStatus::Opened);
        $openRate = $delivered > 0 ? round(100 * $count(OfferStatus::Opened) / $delivered, 1) : null;
        $testMode = in_array((string) config('mail.default'), ['log', 'array'], true);

        return [
            Stat::make('Подготвени понуди', Offer::count())
                ->description('Вкупно креирани')
                ->color('gray'),
            Stat::make('Испратени', $delivered)
                ->description($testMode ? 'Тест-режим: пораките се запишуваат во лог' : 'Доставени до е-пошта')
                ->color('success'),
            Stat::make('Отворени', $count(OfferStatus::Opened))
                ->description($openRate === null ? 'Нема испратени' : $openRate.'% од испратените (индикативно)')
                ->color('info'),
            Stat::make('Неуспешни', $count(OfferStatus::Failed))
                ->description('Може да се повторат од понудата')
                ->color($count(OfferStatus::Failed) > 0 ? 'danger' : 'gray'),
        ];
    }
}
