<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class CompanyStatisticsCommand extends Command
{
    protected $signature = 'app:company-statistics';

    protected $description = 'Print company statistics (Table II)';

    public function handle(): int
    {
        $totalCompanies = Company::count();

        $testedSectors = Company::query()
            ->whereNotNull('sector')
            ->distinct()
            ->count('sector');

        $companiesWithEmailFinal = Company::query()
            ->whereNotNull('email')
            ->count();

        $emailCoverageAfterEnrichmentPercent = $totalCompanies > 0
            ? ($companiesWithEmailFinal / $totalCompanies) * 100
            : 0.0;

        $initialEmailCoveragePercent = (float) config('company_statistics.initial_email_coverage_percent', 0.0);

        $duplicateRecordsCount = Company::query()
            ->where('scrape_count', '>', 1)
            ->count();

        $duplicateReductionRatePercent = $totalCompanies > 0
            ? ($duplicateRecordsCount / $totalCompanies) * 100
            : 0.0;

        $avgScrapingTimePerSectorSeconds = config('company_statistics.average_scraping_time_per_sector_seconds');
        $avgScrapingTimePerSectorDisplay = $avgScrapingTimePerSectorSeconds === null
            ? 'N/A'
            : sprintf('%.2f s', (float) $avgScrapingTimePerSectorSeconds);

        $rows = [
            ['Total Collected Companies', number_format($totalCompanies)],
            ['Tested Sectors', number_format($testedSectors)],
            ['Initial Email Coverage %', sprintf('%.2f%%', $initialEmailCoveragePercent)],
            ['Email Coverage After Enrichment %', sprintf('%.2f%%', $emailCoverageAfterEnrichmentPercent)],
            ['Companies with Email (Final)', number_format($companiesWithEmailFinal)],
            ['Duplicate Reduction Rate %', sprintf('%.2f%%', $duplicateReductionRatePercent)],
            ['Average Scraping Time per Sector', $avgScrapingTimePerSectorDisplay],
        ];

        $this->table(['Metric', 'Value'], $rows);

        return Command::SUCCESS;
    }
}

