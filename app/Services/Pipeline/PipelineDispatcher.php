<?php

namespace App\Services\Pipeline;

use App\Enums\PipelineTask;
use App\Jobs\ProcessCompaniesJob;
use App\Models\Company;
use App\Services\ActivityIndex\ActivityIndexCalculator;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;

class PipelineDispatcher
{
    public const CHUNK_SIZE = 50;

    /**
     * Companies a task still has to process.
     *
     * @return Builder<Company>
     */
    public function pending(PipelineTask $task): Builder
    {
        return match ($task) {
            PipelineTask::Profiles => Company::query()->whereNotNull('profile_url')->whereNull('profile_scraped_at'),
            PipelineTask::CompanyWall => Company::query()->whereNull('companywall_scraped_at'),
            // Companies with a known website first: they are the ones that can succeed.
            PipelineTask::Emails => Company::query()->whereNull('email')->orderByRaw('website IS NULL'),
        };
    }

    /**
     * Queue the task for all pending companies (optionally at most $limit).
     * Returns null when nothing is pending. The activity index is refreshed afterwards.
     */
    public function dispatch(PipelineTask $task, ?int $limit = null, bool $sync = false): ?Batch
    {
        $query = $this->pending($task)->orderBy('id');
        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $jobs = $query->pluck('id')
            ->chunk(self::CHUNK_SIZE)
            ->map(fn ($ids) => new ProcessCompaniesJob($task, $ids->values()->all()))
            ->all();

        if ($jobs === []) {
            return null;
        }

        $pending = Bus::batch($jobs)
            ->name($task->value)
            ->allowFailures()
            ->finally(fn () => app(ActivityIndexCalculator::class)->recalculateAll());

        if ($sync) {
            $pending->onConnection('sync');
        }

        return $pending->dispatch();
    }
}
