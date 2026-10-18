<?php

namespace App\Enums;

enum ScrapeRunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
