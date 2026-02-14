<?php

namespace App\Console\Commands;

use App\Services\EmailEnrichment\EmailEnrichmentService;
use Illuminate\Console\Command;

class EnrichCompanyEmailsCommand extends Command
{
    protected $signature = 'app:enrich-company-emails 
                            {--limit=100 : Number of companies to process}
                            {--all : Process all companies without email}
                            {--skip-existing : Skip companies that already have email}
                            {--detailed : Show detailed output for each company}';

    protected $description = 'Enrich companies with email addresses using multiple strategies';

    public function handle(EmailEnrichmentService $service)
    {
        $limit = $this->option('all') ? PHP_INT_MAX : (int) $this->option('limit');
        $skipExisting = $this->option('skip-existing') ?? true;
        $detailed = $this->option('detailed');

        $this->info('Starting email enrichment...');
        $this->newLine();

        $query = \App\Models\Company::query();
        if ($skipExisting) {
            $query->whereNull('email');
        }
        $totalCount = $query->count();
        $actualLimit = min($limit, $totalCount);

        if ($actualLimit === 0) {
            $this->warn('No companies found to enrich.');

            return Command::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($actualLimit);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $progressBar->start();

        $enriched = 0;
        $failed = 0;
        $enrichedList = [];

        $results = $service->enrichCompanies(
            $limit,
            $skipExisting,
            function ($company, $success, $email = null, $hadEmailBefore = false) use (&$enriched, &$failed, &$enrichedList, $progressBar, $detailed) {
                if ($success && $email && ! $hadEmailBefore) {
                    $enriched++;
                    $enrichedList[] = [
                        'name' => $company->name,
                        'email' => $email,
                    ];
                    if ($detailed) {
                        $this->newLine();
                        $this->line("  [OK] <fg=cyan>{$company->name}</> -> <fg=yellow>{$email}</>");
                    }
                } elseif ($success && $hadEmailBefore) {
                    if ($detailed) {
                        $this->newLine();
                        $this->line("  [SKIP] <fg=gray>{$company->name}</> - веќе има е-пошта");
                    }
                } else {
                    $failed++;
                    if ($detailed) {
                        $this->newLine();
                        $this->line("  [FAIL] <fg=red>{$company->name}</> - не се најде е-пошта");
                    }
                }
                $progressBar->advance();
            }
        );

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Резултати:');
        $this->line("  Вкупно обработени: <fg=cyan>{$results['total']}</>");
        $this->line("  Успешно обогатени: <fg=green>{$results['enriched']}</>");
        $this->line("  Неуспешни: <fg=red>{$results['failed']}</>");

        if ($results['enriched'] > 0) {
            $this->newLine();
            $this->info('Успешно обогатени компании:');
            foreach ($enrichedList as $item) {
                $this->line("  - <fg=cyan>{$item['name']}</> -> <fg=yellow>{$item['email']}</>");
            }
        } else {
            $this->newLine();
            $this->warn('Не се најдени е-пошти. Ова може да е поради rate limiting или недостапни податоци.');
        }

        return Command::SUCCESS;
    }
}
