<?php

use App\Enums\PipelineTask;
use App\Jobs\ProcessCompaniesJob;
use App\Models\Company;
use App\Services\Pipeline\PipelineDispatcher;
use App\Services\Scraping\CompanyWallScraper;
use App\Services\Scraping\ZkProfileScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\DomCrawler\Crawler;

uses(RefreshDatabase::class);

const ZK_PROFILE = <<<'HTML'
<div class="result indetails"><h2><a class="companyname" href="/algoritmika?choice=ae"><span itemprop="name">Алгоритмика</span></a></h2>
<div class="shortdescription"><b>Дејности:</b><p><a href="/edukativni-centri">едукативни центри</a>, <a href="/programiranje">програмирање</a></p></div>
<ul><li><a class="website" href="http://gazibaba.alg.academy/"><span>Веб страна</span></a></li>
<li class="disabled"><a class="facebook" href="https://"><span>Види на</span></a></li>
<li><a class="instagram" href="https://www.instagram.com/algoritmika"><span>Инстаграм</span></a></li></ul></div>
HTML;

const CW_PROFILE = <<<'HTML'
<body><h1>АЛГОРИТМИКА ДООЕЛ Скопје</h1><div>ЕДБ 4044021518834 ЕМБС 7506155 Датум на основање 19.5.2021</div>
<p>Компанијата е класифицирана како мал бизнис според критериумите. Компанијата вкупно брои 12 работници, додека просечната</p></body>
HTML;

it('reads website, social links and activities from a zk.mk profile', function () {
    $data = app(ZkProfileScraper::class)->parse(new Crawler(ZK_PROFILE));

    expect($data['website'])->toBe('http://gazibaba.alg.academy/')
        ->and($data['facebook_url'])->toBeNull()
        ->and($data['instagram_url'])->toBe('https://www.instagram.com/algoritmika')
        ->and($data['activities'])->toBe('едукативни центри, програмирање');
});

it('reads registry identifiers from a CompanyWall profile', function () {
    $data = app(CompanyWallScraper::class)->parse(new Crawler(CW_PROFILE));

    expect($data)->toBe([
        'edb' => '4044021518834',
        'embs' => '7506155',
        'founded_on' => '2021-05-19',
        'company_size' => 'мал',
        'employees' => 12,
    ]);
});

it('attaches a CompanyWall profile only when the name matches', function (string $path, bool $expected) {
    $company = new Company(['name' => 'Алгоритмика', 'city' => 'Скопје']);

    expect(app(CompanyWallScraper::class)->slugMatches($path, $company))->toBe($expected);
})->with([
    'same name and city' => ['/kompanija/'.rawurlencode('алгоритмика-дооел-скопје').'/MMJLDXD', true],
    'same name no city' => ['/kompanija/'.rawurlencode('алгоритмика-дооел').'/X', true],
    'other city' => ['/kompanija/'.rawurlencode('алгоритмика-дооел-битола').'/X', false],
    'longer different name' => ['/kompanija/'.rawurlencode('алгоритмика-едукација-дооел-кавадарци').'/X', false],
    'different company' => ['/kompanija/'.rawurlencode('иннова-ресеарцх-дооел-скопје').'/X', false],
]);

it('queues each pipeline task only for companies that still need it', function () {
    Bus::fake();

    Company::create(['name' => 'A', 'profile_url' => '/a']);
    Company::create(['name' => 'B', 'profile_url' => '/b', 'profile_scraped_at' => now()]);
    Company::create(['name' => 'C']);

    $batch = app(PipelineDispatcher::class)->dispatch(PipelineTask::Profiles);

    Bus::assertBatched(fn ($b) => $b->jobs->count() === 1
        && $b->jobs->first() instanceof ProcessCompaniesJob
        && count($b->jobs->first()->companyIds) === 1);

    expect(app(PipelineDispatcher::class)->pending(PipelineTask::CompanyWall)->count())->toBe(3);
});

it('does not queue anything when nothing is pending', function () {
    Bus::fake();

    expect(app(PipelineDispatcher::class)->dispatch(PipelineTask::Emails))->toBeNull();
    Bus::assertNothingBatched();
});

it('stops and leaves the company pending when CompanyWall shows its sign-up wall', function () {
    $company = Company::create(['name' => 'Алгоритмика', 'city' => 'Скопје']);

    $http = Mockery::mock(App\Services\Scraping\PoliteBrowser::class);
    $http->shouldReceive('isAllowed')->andReturn(true);
    $http->shouldReceive('get')->andReturn(new Crawler('<html></html>', 'https://www.companywall.com.mk/registracija'));
    $http->shouldReceive('pause');

    $scraper = new CompanyWallScraper($http, app(App\Support\CompanyNameNormalizer::class));

    expect(fn () => $scraper->scrape($company))->toThrow(App\Exceptions\SourceBlockedException::class);
    expect($company->fresh()->companywall_scraped_at)->toBeNull();
});
