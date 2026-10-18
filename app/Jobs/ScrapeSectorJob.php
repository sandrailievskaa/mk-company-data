<?php

namespace App\Jobs;

use App\Enums\ScrapeRunStatus;
use App\Enums\SectorEnum;
use App\Models\ScrapeRun;
use App\Services\CompanyScraperService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ScrapeSectorJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public readonly SectorEnum $sector) {}

    public function handle(CompanyScraperService $scraper): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $run = ScrapeRun::create([
            'sector' => $this->sector,
            'status' => ScrapeRunStatus::Running,
            'started_at' => now(),
        ]);

        try {
            $scraper->scrapeCompanies($this->sector->value, $run);

            $this->finish($run, ScrapeRunStatus::Completed);
        } catch (Throwable $e) {
            // A failing sector is recorded but must not stop the other sectors.
            $this->finish($run, ScrapeRunStatus::Failed, $e->getMessage());
        }
    }

    private function finish(ScrapeRun $run, ScrapeRunStatus $status, ?string $error = null): void
    {
        $run->update([
            'status' => $status,
            'finished_at' => now(),
            'duration_seconds' => max(0, (int) $run->started_at->diffInSeconds(now(), true)),
            'error_message' => $error,
        ]);
    }
}
