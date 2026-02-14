<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sentOffers(): HasMany
    {
        return $this->hasMany(SentOffer::class);
    }
}
