<?php

namespace App\Models;

use App\Enums\EmailSource;
use App\Enums\SectorEnum;
use App\Support\CompanyNameNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $casts = [
        'sector' => SectorEnum::class,
        'activity_index' => 'float',
        'scrape_count' => 'integer',
        'data_quality_flag' => 'string',
        'email_source' => EmailSource::class,
        'email_found_at' => 'datetime',
        'sources_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Company $company): void {
            if ($company->isDirty('name') || blank($company->normalized_name)) {
                $company->normalized_name = app(CompanyNameNormalizer::class)->normalize($company->name);
            }
        });
    }

    public function sentOffers(): HasMany
    {
        return $this->hasMany(SentOffer::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function offerTargets(): HasMany
    {
        return $this->hasMany(OfferTarget::class);
    }
}
