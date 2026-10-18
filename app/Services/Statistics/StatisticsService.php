<?php

namespace App\Services\Statistics;

use App\Enums\EmailSource;
use App\Enums\ScrapeRunStatus;
use App\Models\Company;
use App\Models\ScrapeRun;

/**
 * Evaluation metrics of the paper (Table II), computed from the stored data so the
 * numbers can be reproduced at any time.
 */
class StatisticsService
{
    /**
     * @return array{
     *   total_companies: int,
     *   tested_sectors: int,
     *   with_email: int,
     *   email_coverage_percent: float,
     *   inferred_emails: int,
     *   discovered_emails: int,
     *   discovered_coverage_percent: float,
     *   initial_email_coverage_percent: float,
     *   duplicate_reduction_percent: float|null,
     *   avg_scrape_minutes_per_sector: float|null,
     * }
     */
    public function tableTwo(): array
    {
        $total = Company::query()->count();
        $withEmail = Company::query()->whereNotNull('email')->count();
        $inferred = Company::query()->where('email_source', EmailSource::Inferred->value)->count();
        $registry = Company::query()->where('email_source', EmailSource::Registry->value)->count();

        return [
            'total_companies' => $total,
            'tested_sectors' => Company::query()->whereNotNull('sector')->distinct()->count('sector'),
            'with_email' => $withEmail,
            'email_coverage_percent' => $this->percent($withEmail, $total),
            'inferred_emails' => $inferred,
            'discovered_emails' => $withEmail - $inferred,
            'discovered_coverage_percent' => $this->percent($withEmail - $inferred, $total),
            // Share of companies whose e-mail came straight from the registry listing,
            // i.e. before any enrichment.
            'initial_email_coverage_percent' => $this->percent($registry, $total),
            'duplicate_reduction_percent' => $this->duplicateReductionPercent(),
            'avg_scrape_minutes_per_sector' => $this->averageScrapeMinutesPerSector(),
        ];
    }

    /**
     * Share of scraped listings that repeated an already-seen company and were
     * merged into its record instead of creating a duplicate. Null before any run.
     */
    public function duplicateReductionPercent(): ?float
    {
        $runs = ScrapeRun::query()->where('status', ScrapeRunStatus::Completed->value);

        $seen = (int) (clone $runs)->sum('records_seen');

        return $seen > 0 ? $this->percent((int) (clone $runs)->sum('duplicates_skipped'), $seen) : null;
    }

    public function averageScrapeMinutesPerSector(): ?float
    {
        $avg = ScrapeRun::query()
            ->where('status', ScrapeRunStatus::Completed->value)
            ->whereNotNull('duration_seconds')
            ->avg('duration_seconds');

        return $avg === null ? null : round(((float) $avg) / 60, 2);
    }

    private function percent(int $part, int $whole): float
    {
        return $whole > 0 ? round(100 * $part / $whole, 2) : 0.0;
    }
}
