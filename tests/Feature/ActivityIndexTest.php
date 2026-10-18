<?php

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\ActivityIndex\ActivityIndexCalculator;
use App\Services\ActivityIndex\FrequencyScale;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('normalizes frequency into [0, 1] and gives 0 when there is no variation', function () {
    $scale = new FrequencyScale(1, 9);

    expect($scale->normalize(1))->toBe(0.0)
        ->and($scale->normalize(9))->toBe(1.0)
        ->and($scale->normalize(3))->toBeBetween(0.01, 0.99)
        ->and($scale->normalize(100))->toBe(1.0)
        ->and((new FrequencyScale(2, 2))->normalize(2))->toBe(0.0);
});

it('scores E, P and F with the 0.5 / 0.3 / 0.2 weights', function () {
    $calculator = app(ActivityIndexCalculator::class);
    $scale = new FrequencyScale(1, 5);

    $best = new Company(['email' => 'office@firma.mk', 'email_source' => EmailSource::Website, 'scrape_count' => 5]);
    $none = new Company(['scrape_count' => 1]);

    // E=1, P=2/3 (registry + website), F=1  => 0.5 + 0.2 + 0.2
    expect($calculator->score($best, $scale))->toBe(0.9)
        // E=0, P=1/3 (registry only), F=0   => 0.1
        ->and($calculator->score($none, $scale))->toBe(0.1);
});

it('can exclude inferred addresses from E', function () {
    $calculator = app(ActivityIndexCalculator::class);
    $company = new Company(['email' => 'info@firma.mk', 'email_source' => EmailSource::Inferred, 'scrape_count' => 1]);

    expect($calculator->components($company, new FrequencyScale(1, 1))['email'])->toBe(1.0);

    config(['activity_index.inferred_email_counts' => false]);

    expect($calculator->components($company, new FrequencyScale(1, 1))['email'])->toBe(0.0);
});

it('ignores junk addresses for E', function () {
    $company = new Company(['email' => 'abc@sentry.io', 'scrape_count' => 1]);

    expect(app(ActivityIndexCalculator::class)->components($company, new FrequencyScale(1, 1))['email'])->toBe(0.0);
});

it('persists the index and sources_count for every company', function () {
    Company::create(['name' => 'A', 'email' => 'office@a.mk', 'email_source' => EmailSource::Search, 'scrape_count' => 4]);
    Company::create(['name' => 'B', 'scrape_count' => 1]);

    $this->artisan('app:calculate-activity-index')->assertSuccessful();

    $a = Company::where('name', 'A')->first();
    $b = Company::where('name', 'B')->first();

    expect($a->activity_index)->toBe(0.9)
        ->and($a->sources_count)->toBe(2)
        ->and($b->activity_index)->toBe(0.1)
        ->and($b->sources_count)->toBe(1);
});
