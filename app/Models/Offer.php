<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    protected $fillable = [
        'title',
        'content',
        'company_id',
        'status',
    ];

    protected $casts = [
        'status' => OfferStatus::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(OfferTarget::class);
    }
}
