<?php

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\DomainGuesser;
use App\Services\EmailEnrichment\EmailDomainChecker;
use App\Services\EmailEnrichment\EmailEnrichmentService;
use App\Services\EmailEnrichment\EmailExtractor;
use App\Services\EmailEnrichment\SiteEmailFinder;
use App\Services\EmailEnrichment\Strategies\PatternStrategy;
use App\Services\EmailEnrichment\Strategies\SearchStrategy;
use App\Services\EmailEnrichment\Strategies\WebsiteStrategy;
use App\Support\CompanyNameNormalizer;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function enrichmentService(array $responses, bool $mx = true): EmailEnrichmentService
{
    $client = new Client(['handler' => HandlerStack::create(new MockHandler($responses)), 'http_errors' => false]);
    $extractor = new EmailExtractor;
    $domains = app(DomainGuesser::class);
    $finder = new SiteEmailFinder($client, $extractor);

    $checker = new class($mx) extends EmailDomainChecker
    {
        public function __construct(private readonly bool $answer) {}

        protected function lookup(string $domain): bool
        {
            return $this->answer;
        }
    };

    return new EmailEnrichmentService(
        new WebsiteStrategy($finder, $domains),
        new SearchStrategy($client, $finder, app(CompanyNameNormalizer::class)),
        new PatternStrategy($domains),
        $extractor,
        $checker,
    );
}

it('rejects tracker, schema, parking and asset addresses', function (string $email) {
    expect((new EmailExtractor)->isUsable($email))->toBeFalse();
})->with([
    '50365a6fe24f4ff28f6b625410e04a35@sentry.io',
    'public-schemaorg@w3.org',
    'logo@2x.png',
    'noreply@firma.mk',
    'a3f9c2d1e8b74a60aa@firma.mk',
    'interested@domainmarket.com',
]);

it('extracts and prefers a .mk address from html', function () {
    $html = '<a href="mailto:x@gmail.com">x</a> <p>Контакт: sales@firma.mk</p>';

    expect((new EmailExtractor)->extract($html))->toBe('sales@firma.mk');
});

it('reads the address from the website listed on the company profile', function () {
    config(['enrichment.search_enabled' => false]);
    $company = Company::create(['name' => 'Нова Градба', 'website' => 'http://www.novagradba.mk']);

    $service = enrichmentService([new Response(200, [], '<p>office@novagradba.mk</p>')]);

    expect($service->findEmail($company))->toBe('office@novagradba.mk')
        ->and($service->lastSource())->toBe(EmailSource::Website);
});

it('does not guess domains or addresses by default', function () {
    config(['enrichment.search_enabled' => false]);
    $company = Company::create(['name' => 'Нова Градба']);

    $service = enrichmentService(array_fill(0, 50, new Response(404)));

    expect($service->findEmail($company))->toBeNull()
        ->and($service->lastSource())->toBeNull();
});

it('keeps a pattern address only when explicitly enabled and the domain can receive mail', function () {
    config(['enrichment.search_enabled' => false, 'enrichment.inferred_enabled' => true]);
    $company = Company::create(['name' => 'Нова Градба']);

    $withMx = enrichmentService(array_fill(0, 50, new Response(404)), mx: true);
    expect($withMx->findEmail($company))->toBe('info@novagradba.mk')
        ->and($withMx->lastSource())->toBe(EmailSource::Inferred);

    $withoutMx = enrichmentService(array_fill(0, 50, new Response(404)), mx: false);
    expect($withoutMx->findEmail($company))->toBeNull();
});

it('stores source, MX result and discovered website when enriching', function () {
    config(['enrichment.search_enabled' => false, 'scraping.delay_ms' => 0]);
    Company::create(['name' => 'Нова Градба', 'website' => 'http://novagradba.mk']);

    enrichmentService([new Response(200, [], 'office@novagradba.mk')], mx: true)->enrichCompanies(10);

    $company = Company::first();
    expect($company->email)->toBe('office@novagradba.mk')
        ->and($company->email_source)->toBe(EmailSource::Website)
        ->and($company->email_domain_has_mx)->toBeTrue()
        ->and($company->email_found_at)->not->toBeNull();
});

it('accepts a search result only when its domain carries the company name', function (string $url, bool $expected) {
    $strategy = new SearchStrategy(
        new Client,
        new SiteEmailFinder(new Client, new EmailExtractor),
        app(CompanyNameNormalizer::class),
    );

    expect($strategy->domainBelongsTo($url, new Company(['name' => 'Алгоритмика ДООЕЛ'])))->toBe($expected);
})->with([
    'own site' => ['https://algoritmika.mk/kontakt', true],
    'own .com.mk site' => ['http://www.algoritmika.com.mk', true],
    'directory' => ['https://zk.mk/algoritmika', false],
    'social network' => ['https://www.facebook.com/algoritmika', false],
    'unrelated' => ['https://example.org', false],
]);

it('builds URL variants for a stored website', function () {
    expect(SiteEmailFinder::variants('http://www.firma.mk/mk'))->toBe([
        'https://firma.mk/mk', 'http://firma.mk/mk', 'https://www.firma.mk/mk', 'http://www.firma.mk/mk',
    ])->and(SiteEmailFinder::variants('firma.mk'))->toHaveCount(4)
        ->and(SiteEmailFinder::variants(''))->toBe([]);
});

it('clears junk addresses but keeps real ones', function () {
    $junk = Company::create(['name' => 'A', 'email' => 'abc@sentry.io', 'email_source' => EmailSource::Observed]);
    $real = Company::create(['name' => 'B', 'email' => 'info@real.mk', 'email_source' => EmailSource::Observed]);

    $this->artisan('app:clean-junk-emails')->assertSuccessful();

    expect($junk->fresh()->email)->toBeNull()
        ->and($junk->fresh()->email_source)->toBeNull()
        ->and($real->fresh()->email)->toBe('info@real.mk');
});
