<?php

namespace App\Console\Commands;

use App\Enums\PipelineTask;
use App\Services\Pipeline\PipelineDispatcher;
use Illuminate\Console\Command;

/**
 * Base for the commands that run one pipeline task over the companies still pending.
 */
abstract class PipelineTaskCommand extends Command
{
    abstract protected function task(): PipelineTask;

    public function handle(PipelineDispatcher $pipeline): int
    {
        $task = $this->task();
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $pending = $pipeline->pending($task)->count();

        if ($pending === 0) {
            $this->info('Nothing to process.');

            return Command::SUCCESS;
        }

        $sync = (bool) $this->option('sync');
        $batch = $pipeline->dispatch($task, $limit, $sync);
        $count = $limit ? min($pending, $limit) : $pending;

        $this->info(($sync ? 'Processed ' : 'Queued ').$count.' companies ('.$task->label().') in '.$batch->totalJobs.' job(s).');

        if (! $sync) {
            $this->line('Make sure a worker is running: php artisan queue:work');
        }

        return Command::SUCCESS;
    }
}
