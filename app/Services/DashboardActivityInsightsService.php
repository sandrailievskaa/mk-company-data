<?php

namespace App\Services;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;

class DashboardActivityInsightsService
{
    /**
     * Дистрибуција и топ-листа: сите броеви од `companies` (activity_index, име, град, сектор).
     * Линковите се генерирани од Filament CompanyResource.
     *
     * @return array{buckets: list<array{key: string, xlabel: string, tier: string, count: int}>, max_count: int, is_empty: bool}
     */
    public function getActivityIndexDistribution(): array
    {
        if (Company::query()->doesntExist()) {
            return [
                'buckets' => $this->emptyBuckets(),
                'max_count' => 0,
                'is_empty' => true,
            ];
        }

        $row = Company::query()
            ->toBase()
            ->selectRaw(
                'COUNT(CASE WHEN activity_index >= 0 AND activity_index <= 0.2 THEN 1 END) as c0,
                COUNT(CASE WHEN activity_index > 0.2 AND activity_index <= 0.4 THEN 1 END) as c1,
                COUNT(CASE WHEN activity_index > 0.4 AND activity_index <= 0.6 THEN 1 END) as c2,
                COUNT(CASE WHEN activity_index > 0.6 AND activity_index <= 0.8 THEN 1 END) as c3,
                COUNT(CASE WHEN activity_index > 0.8 AND activity_index <= 1.0000001 THEN 1 END) as c4'
            )
            ->first();

        if (! $row) {
            return [
                'buckets' => $this->emptyBuckets(),
                'max_count' => 0,
                'is_empty' => true,
            ];
        }

        $counts = [
            (int) ($row->c0 ?? 0),
            (int) ($row->c1 ?? 0),
            (int) ($row->c2 ?? 0),
            (int) ($row->c3 ?? 0),
            (int) ($row->c4 ?? 0),
        ];
        $maxCount = max($counts) ?: 1;

        $xlabels = ['0–0.2', '0.2–0.4', '0.4–0.6', '0.6–0.8', '0.8–1.0'];
        $tiers = ['Ниско', 'Умерено', 'Средно', 'Високо', 'Елитно'];

        $buckets = [];
        for ($i = 0; $i < 5; $i++) {
            $buckets[] = [
                'key' => 'b'.$i,
                'xlabel' => $xlabels[$i],
                'tier' => $tiers[$i],
                'count' => $counts[$i],
            ];
        }

        return [
            'buckets' => $buckets,
            'max_count' => $maxCount,
            'is_empty' => false,
        ];
    }

    /**
     * @return list<array{id: int, name: string, initial: string, score: int, blurb: string, editUrl: string}>
     */
    public function getTopByActivityIndex(int $limit = 5): array
    {
        $companies = Company::query()
            ->orderByDesc('activity_index')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'city', 'sector', 'activity_index']);

        $out = [];
        foreach ($companies as $c) {
            $name = (string) $c->name;
            $initial = $this->initialLetter($name);
            $raw = (float) ($c->activity_index ?? 0);
            $score = (int) round($raw * 100);
            $score = min(100, max(0, $score));
            $city = is_string($c->city) ? trim($c->city) : '';
            $sector = $c->sector;
            $secLabel = $sector?->getLabel();
            $blurbParts = array_filter([$secLabel, $city !== '' ? $city : null]);
            $blurb = count($blurbParts) > 0
                ? implode(' · ', $blurbParts)
                : 'Секторска погодност · збогатете контакти';

            $out[] = [
                'id' => (int) $c->id,
                'name' => $name,
                'initial' => $initial,
                'score' => $score,
                'blurb' => $blurb,
                'editUrl' => CompanyResource::getUrl('edit', ['record' => $c->id]),
            ];
        }

        return $out;
    }

    public function getActivityChartSubtitle(): string
    {
        return 'Сегментирано низ целата база на податоци.';
    }

    /**
     * @return list<array{key: string, xlabel: string, tier: string, count: int}>
     */
    private function emptyBuckets(): array
    {
        $xlabels = ['0–0.2', '0.2–0.4', '0.4–0.6', '0.6–0.8', '0.8–1.0'];
        $tiers = ['Ниско', 'Умерено', 'Средно', 'Високо', 'Елитно'];
        $buckets = [];
        for ($i = 0; $i < 5; $i++) {
            $buckets[] = [
                'key' => 'b'.$i,
                'xlabel' => $xlabels[$i],
                'tier' => $tiers[$i],
                'count' => 0,
            ];
        }

        return $buckets;
    }

    private function initialLetter(string $name): string
    {
        $t = trim($name);
        if ($t === '') {
            return '?';
        }

        return strtoupper(mb_substr($t, 0, 1, 'UTF-8'));
    }
}
