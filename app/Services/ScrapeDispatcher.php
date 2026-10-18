<?php

namespace App\Services;

use App\Enums\SectorEnum;
use App\Jobs\ScrapeSectorJob;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;

class ScrapeDispatcher
{
    /**
     * Queue one scraping job per sector; the activity index is recalculated once all finish.
     *
     * @param  list<SectorEnum>|null  $sectors  null = all sectors
     */
    public function dispatch(?array $sectors = null, bool $sync = false): Batch
    {
        $sectors ??= SectorEnum::cases();

        $pending = Bus::batch(array_map(fn (SectorEnum $s) => new ScrapeSectorJob($s), $sectors))
            ->name('Scrape companies')
            ->allowFailures()
            ->finally(function () {
                Artisan::call('app:calculate-activity-index');
            });

        if ($sync) {
            $pending->onConnection('sync');
        }

        return $pending->dispatch();
    }
}
