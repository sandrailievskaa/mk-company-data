<?php

namespace App\Models;

use App\Enums\ScrapeRunStatus;
use App\Enums\SectorEnum;
use Illuminate\Database\Eloquent\Model;

class ScrapeRun extends Model
{
    protected $casts = [
        'status' => ScrapeRunStatus::class,
        'sector' => SectorEnum::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
