<?php

use App\Models\Company;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fills normalized_name automatically on save', function () {
    $company = Company::create(['name' => 'Нова Градба ДООЕЛ']);

    expect($company->normalized_name)->toBe('nova gradba');
});

it('merges name variants, keeps contact data and re-points offers', function () {
    $a = Company::create(['name' => 'Нова Градба ДООЕЛ', 'city' => 'Скопје']);
    $b = Company::create(['name' => 'НОВА ГРАДБА', 'email' => 'info@novagradba.mk', 'phone' => '02 123', 'scrape_count' => 3]);
    $offer = Offer::create(['title' => 'T', 'content' => 'C', 'company_id' => $a->id]);

    $this->artisan('app:deduplicate-companies')->assertSuccessful();

    expect(Company::count())->toBe(1);

    $kept = Company::first();
    expect($kept->email)->toBe('info@novagradba.mk')
        ->and($kept->city)->toBe('Скопје')
        ->and($kept->phone)->toBe('02 123')
        ->and($kept->scrape_count)->toBe(3)
        ->and($offer->fresh()->company_id)->toBe($kept->id);
});

it('does not change anything on a dry run', function () {
    Company::create(['name' => 'Нова Градба ДООЕЛ']);
    Company::create(['name' => 'НОВА ГРАДБА']);

    $this->artisan('app:deduplicate-companies --dry-run')->assertSuccessful();

    expect(Company::count())->toBe(2);
});
