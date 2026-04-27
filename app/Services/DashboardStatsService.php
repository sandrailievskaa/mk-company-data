<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardStatsService
{
    /**
     * Сите броеви = Company, Auth user, company_statistics config (поч. e-покривеност, ref. дупл., праг на активност).
     *
     * @return array<string, mixed>
     */
    public function build(?User $user = null): array
    {
        $user = $user ?? Auth::user();
        $now = Carbon::now();
        $activeThreshold = (float) config('company_statistics.active_activity_index_threshold', 0.5);

        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $lastWeekStart = $weekStart->copy()->subWeek();
        $lastWeekEnd = $weekStart->copy()->subSecond();

        $totalCompanies = (int) Company::count();

        $thisWeekNew = (int) Company::query()
            ->whereBetween('created_at', [$weekStart, $now])
            ->count();

        $lastWeekNew = (int) Company::query()
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->count();

        $companiesWeekOverWeekPct = $lastWeekNew > 0
            ? round((($thisWeekNew - $lastWeekNew) / $lastWeekNew) * 100, 1)
            : ($thisWeekNew > 0 ? 100.0 : 0.0);

        $companiesDeltaTone = $lastWeekNew === 0 && $thisWeekNew === 0
            ? 'neutral'
            : ($thisWeekNew >= $lastWeekNew ? 'good' : 'bad');

        $withEmail = (int) Company::query()->whereNotNull('email')->count();
        $emailCoverage = $totalCompanies > 0
            ? round(100.0 * $withEmail / $totalCompanies, 1)
            : 0.0;

        $initialEmail = (float) config('company_statistics.initial_email_coverage_percent', 0.0);
        $initialEmailFormatted = $this->formatPercentOneDecimal($initialEmail);
        $emailDelta = round($emailCoverage - $initialEmail, 1);
        $emailCoverageFormatted = $this->formatPercentOneDecimal($emailCoverage);
        $emailDeltaTone = abs($emailDelta) < 0.05
            ? 'neutral'
            : ($emailDelta >= 0 ? 'good' : 'bad');

        $sectorCount = (int) Company::query()
            ->whereNotNull('sector')
            ->distinct()
            ->count('sector');

        $activeCompanies = (int) Company::query()
            ->where('activity_index', '>', $activeThreshold)
            ->count();

        $activeSharePct = $totalCompanies > 0
            ? round(100.0 * $activeCompanies / $totalCompanies, 1)
            : 0.0;
        $activeActivityIndexThresholdFormatted = rtrim(rtrim(number_format($activeThreshold, 2, '.', ''), '0'), '.') ?: '0';
        $activeShareFormatted = $this->formatPercentOneDecimal($activeSharePct);

        $dupRecords = (int) Company::query()
            ->where('scrape_count', '>', 1)
            ->count();

        $dupRate = $totalCompanies > 0
            ? round(100.0 * $dupRecords / $totalCompanies, 1)
            : 0.0;
        $dupRateFormatted = $this->formatPercentOneDecimal($dupRate);

        $baselineDup = config('company_statistics.baseline_duplicate_rate_percent');
        $dupDelta = is_numeric($baselineDup)
            ? round((float) $baselineDup - $dupRate, 1)
            : null;
        $dupDeltaTone = $dupDelta === null
            ? null
            : (abs((float) $dupDelta) < 0.05
                ? 'neutral'
                : ((float) $dupDelta > 0
                    ? 'good'
                    : 'bad'));

        $displayName = $user?->name
            ? trim((string) $user->name)
            : (string) (str($user?->email ?? 'корисник')->before('@')->value() ?: 'корисник');

        $greeting = 'Здраво';

        $companiesPhrase = $totalCompanies === 1
            ? '1 компанија'
            : number_format($totalCompanies, 0, ',', ' ').' компании';
        $sectorsPhrase = $sectorCount === 1
            ? '1 сектор'
            : number_format($sectorCount, 0, ',', ' ').' сектори';

        return [
            'greeting' => $greeting,
            'userName' => $displayName,
            'subtitle' => sprintf('%s · %s', $companiesPhrase, $sectorsPhrase),
            'activeActivityIndexThreshold' => $activeThreshold,
            'activeActivityIndexThresholdFormatted' => $activeActivityIndexThresholdFormatted,
            'activeShareFormatted' => $activeShareFormatted,
            'initialEmailFormatted' => $initialEmailFormatted,
            'emailCoverageFormatted' => $emailCoverageFormatted,
            'dupRateFormatted' => $dupRateFormatted,
            'totalCompanies' => $totalCompanies,
            'companiesWeekOverWeekPct' => $companiesWeekOverWeekPct,
            'companiesWowFormatted' => sprintf('%+.1f%%', $companiesWeekOverWeekPct),
            'companiesDeltaTone' => $companiesDeltaTone,
            'thisWeekNew' => $thisWeekNew,
            'lastWeekNew' => $lastWeekNew,
            'withEmail' => $withEmail,
            'emailCoverage' => $emailCoverage,
            'emailDelta' => $emailDelta,
            'emailDeltaTone' => $emailDeltaTone,
            'sectorCount' => $sectorCount,
            'activeCompanies' => $activeCompanies,
            'activeSharePct' => $activeSharePct,
            'dupRate' => $dupRate,
            'dupDelta' => $dupDelta,
            'dupDeltaTone' => $dupDeltaTone,
            'dupHasBaseline' => is_numeric($baselineDup),
        ];
    }

    private function formatPercentOneDecimal(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.') ?: '0';
    }
}
