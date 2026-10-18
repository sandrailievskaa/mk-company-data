<?php

namespace App\Services\Offers;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Models\SentOffer;

/**
 * Numbers for the offers row of the dashboard.
 */
class CampaignStats
{
    /**
     * @return array{offers: int, delivered: int, opened: int, failed: int, openRate: float|null, testMode: bool}
     */
    public function summary(): array
    {
        $counts = SentOffer::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $count = fn (OfferStatus $s): int => (int) ($counts[$s->value] ?? 0);

        $opened = $count(OfferStatus::Opened);
        $delivered = $count(OfferStatus::Sent) + $opened;

        return [
            'offers' => Offer::query()->count(),
            'delivered' => $delivered,
            'opened' => $opened,
            'failed' => $count(OfferStatus::Failed),
            'openRate' => $delivered > 0 ? round(100 * $opened / $delivered, 1) : null,
            'testMode' => in_array((string) config('mail.default'), ['log', 'array'], true),
        ];
    }
}
