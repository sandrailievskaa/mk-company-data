<?php

namespace App\Console\Commands;

use App\Enums\SectorEnum;
use App\Services\CompanyScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

class ScrapeCompaniesCommand extends Command
{
    protected $signature = 'app:scrape-companies-command';

    protected $description = 'Scraping the companies from ZK';

    public function handle()
    {
        $service = resolve(CompanyScraperService::class);

        $sectors = [
            SectorEnum::CONSTRUCTION->value,
            SectorEnum::PROGRAMMING->value,
            SectorEnum::HEALTHCARE->value,
            SectorEnum::TRAVELAGENCIES->value,
            SectorEnum::BANKS->value,
            SectorEnum::MUNICIPALITIES->value,
            SectorEnum::EDUCATION->value,
            SectorEnum::AIRPLANE->value,
            SectorEnum::INSURANCE->value,
            SectorEnum::FINANCE->value,
        ];

        $output = new ConsoleOutput;
        $progressBar = new ProgressBar($output, count($sectors));

        $progressBar->setFormat(
            ' %current%/%max% [%bar%] %percent:3s%% | %message%'
        );

        $progressBar->start();

        foreach ($sectors as $sector) {
            $progressBar->setMessage("Scraping: {$sector}");
            try {
                $service->scrapeCompanies($sector);
            } catch (Throwable $e) {
                $this->newLine();
                $this->warn("Failed scraping sector {$sector}: {$e->getMessage()}");
            }
            $progressBar->advance();
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info('Recalculating activity index...');
        try {
            Artisan::call('app:calculate-activity-index', [], $this->output);
        } catch (Throwable $e) {
            $this->newLine();
            $this->warn("Failed calculating activity index: {$e->getMessage()}");
        }
    }
}
