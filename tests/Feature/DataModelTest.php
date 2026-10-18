<?php

use App\Enums\EmailSource;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Offer;
use App\Models\SentOffer;
use App\Services\EmailEnrichment\EmailEnrichmentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('classifies pattern-generated addresses as inferred and others as observed', function () {
    $generated = Company::create(['name' => 'Нова Градба', 'email' => 'info@novagradba.mk']);
    $real = Company::create(['name' => 'Фани ком', 'email' => 'fani.kom71@gmail.com']);
    $noEmail = Company::create(['name' => 'Без пошта']);

    $this->artisan('app:backfill-email-source')->assertSuccessful();

    expect($generated->fresh()->email_source)->toBe(EmailSource::Inferred)
        ->and($real->fresh()->email_source)->toBe(EmailSource::Observed)
        ->and($noEmail->fresh()->email_source)->toBeNull();
});

it('does not write anything on a dry run', function () {
    $company = Company::create(['name' => 'Нова Градба', 'email' => 'info@novagradba.mk']);

    $this->artisan('app:backfill-email-source --dry-run')->assertSuccessful();

    expect($company->fresh()->email_source)->toBeNull();
});

it('only treats info@ with a generated domain as a pattern', function () {
    $company = new Company(['name' => 'Нова Градба']);
    $service = app(EmailEnrichmentService::class);

    expect($service->isGeneratedPattern($company, 'info@novagradba.mk'))->toBeTrue()
        ->and($service->isGeneratedPattern($company, 'sales@novagradba.mk'))->toBeFalse()
        ->and($service->isGeneratedPattern($company, 'info@other.mk'))->toBeFalse();
});

it('never allows the same offer to be recorded twice for a company', function () {
    $company = Company::create(['name' => 'Test']);
    $offer = Offer::create(['title' => 'T', 'content' => 'C', 'company_id' => $company->id]);

    $first = SentOffer::create(['offer_id' => $offer->id, 'company_id' => $company->id]);

    expect($first->status)->toBe(OfferStatus::Pending)
        ->and($first->token)->toHaveLength(40);

    SentOffer::create(['offer_id' => $offer->id, 'company_id' => $company->id]);
})->throws(QueryException::class);
