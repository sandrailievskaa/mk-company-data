<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SentOffer extends Model
{
    protected $attributes = [
        'status' => 'pending',
    ];

    protected $casts = [
        'status' => OfferStatus::class,
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SentOffer $sentOffer): void {
            $sentOffer->token ??= Str::random(40);
        });
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
