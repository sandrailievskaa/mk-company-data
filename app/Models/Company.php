<?php

namespace App\Models;

use App\Enums\SectorEnum;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $casts = [
        'sector' => SectorEnum::class,
        'activity_index' => 'integer',
    ];
}
