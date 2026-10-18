<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\ActivityIndex\ActivityIndexCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateActivityIndexCommand extends Command
{
    protected $signature = 'app:calculate-activity-index';

    protected $description = 'Recalculate activity_index for all companies (AI = 0.5*E + 0.3*P + 0.2*F)';

    public function handle(ActivityIndexCalculator $calculator): int
    {
        if (Company::query()->doesntExist()) {
            $this->warn('No companies found.');

            return Command::SUCCESS;
        }

        $this->configureSqliteForConcurrency();

        $stats = $calculator->recalculateAll();

        $this->info('Activity index recalculated.');
        $this->line(sprintf('Companies: %d', $stats['count']));
        $this->line(sprintf('Min: %.4f', $stats['min']));
        $this->line(sprintf('Max: %.4f', $stats['max']));
        $this->line(sprintf('Avg: %.4f', $stats['avg']));

        return Command::SUCCESS;
    }

    private function configureSqliteForConcurrency(): void
    {
        try {
            if (DB::getDriverName() !== 'sqlite') {
                return;
            }

            // Helps SQLite wait instead of immediately failing with "database is locked".
            DB::statement('PRAGMA busy_timeout = 10000');
        } catch (\Throwable) {
            // Best-effort only.
        }
    }
}
