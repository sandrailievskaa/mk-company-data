<?php

namespace App\Console\Commands;

use App\Enums\PipelineTask;

class RunEmailEnrichmentCommand extends PipelineTaskCommand
{
    protected $signature = 'app:run-email-enrichment
                            {--limit= : Process at most this many companies}
                            {--sync : Run in this process instead of the queue}';

    protected $description = 'Find e-mails from company websites and search results in the background';

    protected function task(): PipelineTask
    {
        return PipelineTask::Emails;
    }
}
