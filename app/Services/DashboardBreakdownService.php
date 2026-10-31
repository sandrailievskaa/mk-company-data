<?php

namespace App\Services;

use App\Enums\SectorEnum;
use App\Models\Company;

/**
 * Real numbers for the dashboard charts: how complete the contact data is per sector, and how many
 * companies survive each step from "known company" to "usable, verified e-mail".
 */
class DashboardBreakdownService
{
    /** Sectors with fewer companies than this are flagged as a small sample. */
    public const SMALL_SAMPLE = 30;

    /**
     * @return list<array{label: string, count: int, withEmail: int, coverage: float, avgIndex: float, small: bool}>
     */
    public function sectors(): array
    {
        $rows = Company::query()
            ->toBase()
            ->whereNotNull('sector')
            ->selectRaw("sector, COUNT(*) as total, SUM(CASE WHEN email IS NOT NULL AND email != '' AND (email_source IS NULL OR email_source != 'inferred') THEN 1 ELSE 0 END) as with_email, AVG(activity_index) as avg_index")
            ->groupBy('sector')
            ->get();

        return $rows
            ->map(function (object $row): ?array {
                $sector = SectorEnum::tryFrom((string) $row->sector);
                if ($sector === null) {
                    return null;
                }

                $count = (int) $row->total;
                $withEmail = (int) $row->with_email;

                return [
                    'label' => (string) $sector->getLabel(),
                    'count' => $count,
                    'withEmail' => $withEmail,
                    'coverage' => $count > 0 ? round(100 * $withEmail / $count, 1) : 0.0,
                    'avgIndex' => round((float) $row->avg_index, 2),
                    'small' => $count < self::SMALL_SAMPLE,
                ];
            })
            ->filter()
            ->sortByDesc('coverage')
            ->values()
            ->all();
    }

    /**
     * @return array{steps: list<array{key: string, label: string, count: int, pct: float}>, inferred: int}
     */
    public function funnel(): array
    {
        $row = Company::query()
            ->toBase()
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN website IS NOT NULL AND website != '' THEN 1 ELSE 0 END) as with_website,
                SUM(CASE WHEN email IS NOT NULL AND email != '' AND (email_source IS NULL OR email_source != 'inferred') THEN 1 ELSE 0 END) as with_email,
                SUM(CASE WHEN email IS NOT NULL AND email != '' AND (email_source IS NULL OR email_source != 'inferred') AND email_domain_has_mx = 1 THEN 1 ELSE 0 END) as with_mx,
                SUM(CASE WHEN email_source = 'inferred' THEN 1 ELSE 0 END) as inferred")
            ->first();

        $total = (int) ($row->total ?? 0);
        $counts = [
            'total' => $total,
            'website' => (int) ($row->with_website ?? 0),
            'email' => (int) ($row->with_email ?? 0),
            'mx' => (int) ($row->with_mx ?? 0),
        ];
        $labels = [
            'total' => __('Компании во регистарот'),
            'website' => __('Со јавен веб-сајт'),
            'email' => __('Со пронајдена е-пошта'),
            'mx' => __('Домен што прима пошта (MX)'),
        ];

        $steps = [];
        foreach ($counts as $key => $count) {
            $steps[] = [
                'key' => $key,
                'label' => $labels[$key],
                'count' => $count,
                'pct' => $total > 0 ? round(100 * $count / $total, 1) : 0.0,
            ];
        }

        return ['steps' => $steps, 'inferred' => (int) ($row->inferred ?? 0)];
    }
}
