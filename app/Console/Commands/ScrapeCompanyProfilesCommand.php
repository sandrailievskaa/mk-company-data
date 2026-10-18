<?php

namespace App\Console\Commands;

use App\Enums\PipelineTask;

class ScrapeCompanyProfilesCommand extends PipelineTaskCommand
{
    protected $signature = 'app:scrape-company-profiles
                            {--limit= : Process at most this many companies}
                            {--sync : Run in this process instead of the queue}';

    protected $description = 'Read each company zk.mk profile (website, social links, activities)';

    protected function task(): PipelineTask
    {
        return PipelineTask::Profiles;
    }
}
