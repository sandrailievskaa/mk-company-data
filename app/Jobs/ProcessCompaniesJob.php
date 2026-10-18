<?php

namespace App\Jobs;

use App\Enums\PipelineTask;
use App\Exceptions\SourceBlockedException;
use App\Models\Company;
use App\Services\EmailEnrichment\EmailEnrichmentService;
use App\Services\Scraping\CompanyWallScraper;
use App\Services\Scraping\PoliteBrowser;
use App\Services\Scraping\ZkProfileScraper;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs one pipeline task for a chunk of companies. A failure on one company is
 * logged and does not stop the rest of the chunk.
 */
class ProcessCompaniesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    /** @param list<int> $companyIds */
    public function __construct(
        public readonly PipelineTask $task,
        public readonly array $companyIds,
    ) {}

    public function handle(
        ZkProfileScraper $profiles,
        CompanyWallScraper $companyWall,
        EmailEnrichmentService $emails,
        PoliteBrowser $http,
    ): void {
        foreach (Company::query()->whereIn('id', $this->companyIds)->get() as $company) {
            if ($this->batch()?->cancelled()) {
                return;
            }

            try {
                match ($this->task) {
                    PipelineTask::Profiles => $profiles->scrape($company),
                    PipelineTask::CompanyWall => $companyWall->scrape($company),
                    PipelineTask::Emails => $emails->enrichCompany($company),
                };
            } catch (SourceBlockedException $e) {
                // The source closed its anonymous access: leave the rest pending for a later run.
                Log::warning("{$this->task->value} stopped: ".$e->getMessage());
                $this->batch()?->cancel();

                return;
            } catch (Throwable $e) {
                Log::warning("{$this->task->value} failed for company {$company->id}: ".$e->getMessage());
            }

            $http->pause();
        }
    }
}
