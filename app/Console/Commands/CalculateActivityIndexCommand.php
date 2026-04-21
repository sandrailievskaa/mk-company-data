<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CalculateActivityIndexCommand extends Command
{
    protected $signature = 'app:calculate-activity-index';

    protected $description = 'Recalculate activity_index for all companies';

    public function handle(): int
    {
        $this->configureSqliteForConcurrency();

        $minScrape = (int) (Company::min('scrape_count') ?? 0);
        $maxScrape = (int) (Company::max('scrape_count') ?? 0);

        $count = Company::count();
        if ($count === 0) {
            $this->warn('No companies found.');

            return Command::SUCCESS;
        }

        $denominator = $maxScrape - $minScrape;

        $minAi = null;
        $maxAi = null;
        $sumAi = 0.0;

        Company::query()
            ->select(['id', 'email', 'phone', 'address', 'scrape_count'])
            ->orderBy('id')
            ->chunkById(200, function ($companies) use ($denominator, $minScrape, &$minAi, &$maxAi, &$sumAi) {
                foreach ($companies as $company) {
                    $e = $this->hasValidEmail($company->email) ? 1.0 : 0.0;
                    $p = $this->hasPhoneAndAddress($company->phone, $company->address) ? 1.0 : 0.0;

                    $f = 1.0;
                    if ($denominator > 0) {
                        $f = ((float) $company->scrape_count - (float) $minScrape) / (float) $denominator;
                        $f = max(0.0, min(1.0, $f));
                    }

                    $ai = 0.5 * $e + 0.3 * $p + 0.2 * $f;

                    $this->updateWithRetry($company->id, $ai);

                    $sumAi += $ai;
                    $minAi = $minAi === null ? $ai : min($minAi, $ai);
                    $maxAi = $maxAi === null ? $ai : max($maxAi, $ai);
                }
            });

        $avgAi = $sumAi / $count;

        $this->info('Activity index recalculated.');
        $this->line(sprintf('Min: %.4f', (float) $minAi));
        $this->line(sprintf('Max: %.4f', (float) $maxAi));
        $this->line(sprintf('Avg: %.4f', (float) $avgAi));

        return Command::SUCCESS;
    }

    private function hasValidEmail(?string $email): bool
    {
        if (! is_string($email) || trim($email) === '') {
            return false;
        }

        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }

    private function hasPhoneAndAddress(?string $phone, ?string $address): bool
    {
        $phone = is_string($phone) ? trim($phone) : '';
        $address = is_string($address) ? trim($address) : '';

        return $phone !== '' && $address !== '';
    }

    private function updateWithRetry(int $companyId, float $activityIndex): void
    {
        $attempts = 8;
        $delayMs = 100;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                Company::whereKey($companyId)->update([
                    'activity_index' => $activityIndex,
                ]);

                return;
            } catch (QueryException $e) {
                if (! str_contains(strtolower($e->getMessage()), 'database is locked') || $i === $attempts) {
                    throw $e;
                }

                usleep($delayMs * 1000);
                $delayMs = min($delayMs * 2, 2000);
            }
        }
    }

    private function configureSqliteForConcurrency(): void
    {
        try {
            if (DB::getDriverName() !== 'sqlite') {
                return;
            }

            // Helps SQLite wait instead of immediately failing with "database is locked".
            DB::statement('PRAGMA busy_timeout = 10000');

            // WAL improves concurrent read/write behavior (best-effort; may be a no-op depending on env).
            DB::statement('PRAGMA journal_mode = WAL');
        } catch (\Throwable) {
            // Best-effort only.
        }
    }
}

