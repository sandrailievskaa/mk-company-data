<?php

use App\Enums\ScrapeRunStatus;
use App\Enums\SectorEnum;
use App\Jobs\ScrapeSectorJob;
use App\Models\ScrapeRun;
use App\Services\CompanyScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records a completed run with timing', function () {
    $this->mock(CompanyScraperService::class)
        ->shouldReceive('scrapeCompanies')
        ->once();

    (new ScrapeSectorJob(SectorEnum::PROGRAMMING))->handle(app(CompanyScraperService::class));

    $run = ScrapeRun::first();
    expect($run->status)->toBe(ScrapeRunStatus::Completed)
        ->and($run->sector)->toBe(SectorEnum::PROGRAMMING)
        ->and($run->finished_at)->not->toBeNull()
        ->and($run->duration_seconds)->toBeInt();
});

it('records a failed run instead of throwing', function () {
    $this->mock(CompanyScraperService::class)
        ->shouldReceive('scrapeCompanies')
        ->andThrow(new RuntimeException('boom'));

    (new ScrapeSectorJob(SectorEnum::BANKS))->handle(app(CompanyScraperService::class));

    $run = ScrapeRun::first();
    expect($run->status)->toBe(ScrapeRunStatus::Failed)
        ->and($run->error_message)->toBe('boom');
});
