<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Services\Statistics\StatisticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardStatsService
{
    public function __construct(private readonly StatisticsService $statistics) {}

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

        $table = $this->statistics->tableTwo();
        $withEmail = $table['with_email'];
        $inferredEmails = $table['inferred_emails'];
        $emailCoverage = $table['email_coverage_percent'];

        $initialEmail = $table['initial_email_coverage_percent'];
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
        $activeActivityIndexThresholdFormatted = \App\Support\Fmt::num($activeThreshold, 2, true);
        $activeShareFormatted = $this->formatPercentOneDecimal($activeSharePct);

        // Duplicate listings merged by name normalization, measured from the scrape runs.
        $dupRate = $table['duplicate_reduction_percent'];
        $dupRateFormatted = $dupRate === null ? '—' : $this->formatPercentOneDecimal($dupRate);

        $baselineDup = config('company_statistics.baseline_duplicate_rate_percent');
        $dupDelta = is_numeric($baselineDup) && $dupRate !== null
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
            : (string) (str($user?->email ?? __('корисник'))->before('@')->value() ?: __('корисник'));

        $greeting = __('Здраво');

        $companiesPhrase = $totalCompanies === 1
            ? __('1 компанија')
            : __(':count компании', ['count' => \App\Support\Fmt::num($totalCompanies)]);
        $sectorsPhrase = $sectorCount === 1
            ? __('1 сектор')
            : __(':count сектори', ['count' => \App\Support\Fmt::num($sectorCount)]);

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
            'companiesWowFormatted' => \App\Support\Fmt::signed($companiesWeekOverWeekPct).'%',
            'companiesDeltaTone' => $companiesDeltaTone,
            'thisWeekNew' => $thisWeekNew,
            'lastWeekNew' => $lastWeekNew,
            'withEmail' => $withEmail,
            'inferredEmails' => $inferredEmails,
            'emailCoverage' => $emailCoverage,
            'emailDelta' => $emailDelta,
            'emailDeltaTone' => $emailDeltaTone,
            'sectorCount' => $sectorCount,
            'activeCompanies' => $activeCompanies,
            'activeSharePct' => $activeSharePct,
            'dupRate' => $dupRate,
            'dupDelta' => $dupDelta,
            'dupDeltaTone' => $dupDeltaTone,
            'dupHasBaseline' => is_numeric($baselineDup) && $dupRate !== null,
        ];
    }

    private function formatPercentOneDecimal(float $v): string
    {
        return \App\Support\Fmt::num($v, 1, true);
    }
}
