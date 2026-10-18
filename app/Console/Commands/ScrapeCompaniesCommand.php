<?php

namespace App\Console\Commands;

use App\Enums\SectorEnum;
use App\Models\ScrapeRun;
use App\Services\ScrapeDispatcher;
use Illuminate\Console\Command;

class ScrapeCompaniesCommand extends Command
{
    protected $signature = 'app:scrape-companies-command
                            {--sync : Run in this process instead of the queue and wait for the result}
                            {--sector=* : Limit to specific sectors (enum name, e.g. PROGRAMMING)}';

    protected $description = 'Scraping the companies from ZK';

    public function handle(ScrapeDispatcher $dispatcher): int
    {
        $sectors = $this->resolveSectors();
        if ($sectors === false) {
            return Command::FAILURE;
        }

        $startedAt = now();
        $batch = $dispatcher->dispatch($sectors, (bool) $this->option('sync'));

        if (! $this->option('sync')) {
            $this->info('Queued '.$batch->totalJobs.' sector job(s). Make sure a worker is running: php artisan queue:work');
            $this->line('Follow progress in the admin panel under "Скрејпања".');

            return Command::SUCCESS;
        }

        $runs = ScrapeRun::query()->where('created_at', '>=', $startedAt->copy()->subSecond())->orderBy('id')->get();
        $this->table(
            ['Sector', 'Status', 'Seconds', 'Pages', 'Seen', 'New', 'Updated', 'Emails'],
            $runs->map(fn (ScrapeRun $r) => [
                $r->sector?->getLabel(), $r->status->value, $r->duration_seconds,
                $r->pages_fetched, $r->records_seen, $r->records_created, $r->records_updated, $r->emails_found,
            ])->all()
        );

        return Command::SUCCESS;
    }

    /**
     * @return list<SectorEnum>|null|false null = all sectors, false = invalid input
     */
    private function resolveSectors(): array|null|false
    {
        $names = array_map('strtoupper', (array) $this->option('sector'));
        if ($names === []) {
            return null;
        }

        $sectors = [];
        foreach ($names as $name) {
            $case = collect(SectorEnum::cases())->first(fn (SectorEnum $s) => $s->name === $name);
            if (! $case) {
                $this->error("Unknown sector: {$name}. Valid: ".implode(', ', array_map(fn ($s) => $s->name, SectorEnum::cases())));

                return false;
            }
            $sectors[] = $case;
        }

        return $sectors;
    }
}
