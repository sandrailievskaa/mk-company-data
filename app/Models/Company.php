<?php

namespace App\Models;

use App\Enums\SectorEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $casts = [
        'sector' => SectorEnum::class,
        'activity_index' => 'float',
        'scrape_count' => 'integer',
        'data_quality_flag' => 'string',
    ];

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function offerTargets(): HasMany
    {
        return $this->hasMany(OfferTarget::class);
    }
}
