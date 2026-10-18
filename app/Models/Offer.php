<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Collection;
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
        'scheduled_at' => 'datetime',
        'schedule_dispatched_at' => 'datetime',
    ];

    /** Waiting for the scheduler to send it. */
    public function isScheduled(): bool
    {
        return $this->scheduled_at !== null && $this->schedule_dispatched_at === null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sentOffers(): HasMany
    {
        return $this->hasMany(SentOffer::class);
    }

    /**
     * Companies this offer is meant for: its primary company plus all targets.
     *
     * @return Collection<int, Company>
     */
    public function recipients(): Collection
    {
        $ids = $this->targets()->pluck('company_id')->push($this->company_id)->filter()->unique();

        return Company::query()->whereIn('id', $ids)->orderBy('name')->get();
    }

    public function targets(): HasMany
    {
        return $this->hasMany(OfferTarget::class);
    }
}
