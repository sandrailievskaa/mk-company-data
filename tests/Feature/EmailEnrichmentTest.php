<?php

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\DomainGuesser;
use App\Services\EmailEnrichment\EmailEnrichmentService;
use App\Services\EmailEnrichment\EmailExtractor;
use App\Services\EmailEnrichment\Strategies\PatternStrategy;
use App\Services\EmailEnrichment\Strategies\SearchStrategy;
use App\Services\EmailEnrichment\Strategies\WebsiteStrategy;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function enrichmentService(array $responses): EmailEnrichmentService
{
    $client = new Client(['handler' => HandlerStack::create(new MockHandler($responses)), 'http_errors' => false]);
    $extractor = new EmailExtractor;
    $domains = app(DomainGuesser::class);

    return new EmailEnrichmentService(
        new WebsiteStrategy($client, $domains, $extractor),
        new SearchStrategy($client, $extractor),
        new PatternStrategy($domains),
        $extractor,
    );
}

it('rejects tracker, schema and asset addresses', function (string $email) {
    expect((new EmailExtractor)->isUsable($email))->toBeFalse();
})->with([
    '50365a6fe24f4ff28f6b625410e04a35@sentry.io',
    'public-schemaorg@w3.org',
    'logo@2x.png',
    'noreply@firma.mk',
    'a3f9c2d1e8b74a60aa@firma.mk',
]);

it('extracts and prefers a .mk address from html', function () {
    $html = '<a href="mailto:x@gmail.com">x</a> <p>Контакт: sales@firma.mk</p>';

    expect((new EmailExtractor)->extract($html))->toBe('sales@firma.mk');
});

it('records a website-found address as website source', function () {
    config(['enrichment.search_enabled' => false]);
    $company = Company::create(['name' => 'Нова Градба ДООЕЛ']);

    $service = enrichmentService([new Response(200, [], '<p>office@novagradba.mk</p>')]);

    expect($service->findEmail($company))->toBe('office@novagradba.mk')
        ->and($service->lastSource())->toBe(EmailSource::Website);
});

it('falls back to an inferred pattern address when nothing is found', function () {
    config(['enrichment.search_enabled' => false]);
    $company = Company::create(['name' => 'Нова Градба ДООЕЛ']);

    // Every website probe and contact page answers 404.
    $service = enrichmentService(array_fill(0, 300, new Response(404)));

    expect($service->findEmail($company))->toBe('info@novagradba.mk')
        ->and($service->lastSource())->toBe(EmailSource::Inferred);
});

it('stores the source when enriching a company', function () {
    config(['enrichment.search_enabled' => false, 'scraping.delay_ms' => 0]);
    Company::create(['name' => 'Нова Градба ДООЕЛ']);

    enrichmentService(array_fill(0, 300, new Response(404)))->enrichCompanies(10);

    $company = Company::first();
    expect($company->email)->toBe('info@novagradba.mk')
        ->and($company->email_source)->toBe(EmailSource::Inferred)
        ->and($company->email_found_at)->not->toBeNull();
});

it('clears junk addresses but keeps real ones', function () {
    $junk = Company::create(['name' => 'A', 'email' => 'abc@sentry.io', 'email_source' => EmailSource::Observed]);
    $real = Company::create(['name' => 'B', 'email' => 'info@real.mk', 'email_source' => EmailSource::Observed]);

    $this->artisan('app:clean-junk-emails')->assertSuccessful();

    expect($junk->fresh()->email)->toBeNull()
        ->and($junk->fresh()->email_source)->toBeNull()
        ->and($real->fresh()->email)->toBe('info@real.mk');
});
