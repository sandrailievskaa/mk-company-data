<?php

namespace App\Console\Commands;

use App\Enums\SectorEnum;
use App\Services\CompanyScraperService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class ScrapeCompaniesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scrape-companies-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scraping the companies from ZK';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = resolve(CompanyScraperService::class);

        $sectors = [
            SectorEnum::CONSTRUCTION->value,
            SectorEnum::PROGRAMMING->value,
            SectorEnum::HEALTHCARE->value,
        ];

        $output = new ConsoleOutput;
        $progressBar = new ProgressBar($output, count($sectors));

        $progressBar->setFormat(
            ' %current%/%max% [%bar%] %percent:3s%% | %message%'
        );

        $progressBar->start();

        foreach ($sectors as $sector) {
            $progressBar->setMessage("Scraping: {$sector}");
            $service->scrapeCompanies($sector);
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
