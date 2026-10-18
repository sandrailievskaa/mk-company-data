<?php

use App\Enums\EmailSource;
use App\Enums\ScrapeRunStatus;
use App\Models\Company;
use App\Models\ScrapeRun;
use App\Services\Statistics\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('computes email coverage with the inferred share and the registry baseline', function () {
    Company::create(['name' => 'A', 'sector' => 'programiranje', 'email' => 'a@a.mk', 'email_source' => EmailSource::Registry]);
    Company::create(['name' => 'B', 'sector' => 'zdravstvo', 'email' => 'info@b.mk', 'email_source' => EmailSource::Inferred]);
    Company::create(['name' => 'C', 'sector' => 'zdravstvo']);
    Company::create(['name' => 'D', 'sector' => 'zdravstvo']);

    $t = app(StatisticsService::class)->tableTwo();

    expect($t['total_companies'])->toBe(4)
        ->and($t['tested_sectors'])->toBe(2)
        ->and($t['with_email'])->toBe(2)
        ->and($t['email_coverage_percent'])->toBe(50.0)
        ->and($t['inferred_emails'])->toBe(1)
        ->and($t['discovered_emails'])->toBe(1)
        ->and($t['discovered_coverage_percent'])->toBe(25.0)
        ->and($t['initial_email_coverage_percent'])->toBe(25.0);
});

it('reports duplicate reduction and scraping time only from completed runs', function () {
    ScrapeRun::create(['status' => ScrapeRunStatus::Completed, 'records_seen' => 100, 'duplicates_skipped' => 5, 'duration_seconds' => 120]);
    ScrapeRun::create(['status' => ScrapeRunStatus::Completed, 'records_seen' => 100, 'duplicates_skipped' => 5, 'duration_seconds' => 240]);
    ScrapeRun::create(['status' => ScrapeRunStatus::Failed, 'records_seen' => 999, 'duplicates_skipped' => 999, 'duration_seconds' => 1]);

    $service = app(StatisticsService::class);

    expect($service->duplicateReductionPercent())->toBe(5.0)
        ->and($service->averageScrapeMinutesPerSector())->toBe(3.0);
});

it('returns null metrics when nothing was scraped yet', function () {
    $service = app(StatisticsService::class);

    expect($service->duplicateReductionPercent())->toBeNull()
        ->and($service->averageScrapeMinutesPerSector())->toBeNull();
});

it('prints Table II and can export it as CSV', function () {
    Company::create(['name' => 'A', 'sector' => 'programiranje', 'email' => 'a@a.mk', 'email_source' => EmailSource::Website]);
    $path = sys_get_temp_dir().'/table2-test.csv';

    $this->artisan('app:company-statistics', ['--csv' => $path])->assertSuccessful();

    expect(file_get_contents($path))->toContain('Total Collected Companies');
    @unlink($path);
});
