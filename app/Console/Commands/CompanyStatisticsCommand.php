<?php

namespace App\Console\Commands;

use App\Services\Statistics\StatisticsService;
use Illuminate\Console\Command;

class CompanyStatisticsCommand extends Command
{
    protected $signature = 'app:company-statistics {--csv= : Also write the table to this CSV file}';

    protected $description = 'Print company statistics (Table II)';

    public function handle(StatisticsService $statistics): int
    {
        $s = $statistics->tableTwo();

        $rows = [
            ['Total Collected Companies', number_format($s['total_companies'])],
            ['Tested Sectors', number_format($s['tested_sectors'])],
            ['Initial Email Coverage % (registry listing)', sprintf('%.2f%%', $s['initial_email_coverage_percent'])],
            ['Email Coverage After Enrichment %', sprintf('%.2f%%', $s['email_coverage_percent'])],
            ['Companies with Email (Final)', number_format($s['with_email'])],
            ['  of which discovered (website/search/registry/manual)', number_format($s['discovered_emails']).sprintf(' (%.2f%%)', $s['discovered_coverage_percent'])],
            ['  of which inferred (pattern-generated, unverified)', number_format($s['inferred_emails'])],
            ['Duplicate Reduction Rate %', $s['duplicate_reduction_percent'] === null ? 'N/A (no scrape runs)' : sprintf('%.2f%%', $s['duplicate_reduction_percent'])],
            ['Average Scraping Time per Sector', $s['avg_scrape_minutes_per_sector'] === null ? 'N/A (no scrape runs)' : sprintf('%.2f min', $s['avg_scrape_minutes_per_sector'])],
        ];

        $this->table(['Metric', 'Value'], $rows);

        if ($path = $this->option('csv')) {
            $handle = fopen($path, 'w');
            fputcsv($handle, ['Metric', 'Value']);
            foreach ($rows as $row) {
                fputcsv($handle, [trim($row[0]), $row[1]]);
            }
            fclose($handle);
            $this->info("Written to {$path}");
        }

        return Command::SUCCESS;
    }
}
