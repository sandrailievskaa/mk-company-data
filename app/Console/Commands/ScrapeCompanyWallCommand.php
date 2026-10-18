<?php

namespace App\Console\Commands;

use App\Enums\PipelineTask;

class ScrapeCompanyWallCommand extends PipelineTaskCommand
{
    protected $signature = 'app:scrape-companywall
                            {--limit= : Process at most this many companies}
                            {--sync : Run in this process instead of the queue}';

    protected $description = 'Look companies up on CompanyWall (tax number, registration number, size)';

    protected function task(): PipelineTask
    {
        return PipelineTask::CompanyWall;
    }
}
