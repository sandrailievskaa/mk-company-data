<?php

use App\Enums\EmailSource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Offers\OfferResource;
use App\Filament\Resources\ScrapeRuns\ScrapeRunResource;
use App\Models\Company;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->company = Company::create([
        'name' => 'Нова Градба', 'sector' => 'gradezhni-kompanii', 'city' => 'Скопје',
        'email' => 'info@novagradba.mk', 'email_source' => EmailSource::Inferred,
    ]);
    $this->offer = Offer::create(['title' => 'Понуда', 'content' => 'Текст', 'company_id' => $this->company->id]);
});

it('renders the admin pages without errors', function (string $page) {
    $url = match ($page) {
        'dashboard' => '/admin',
        'companies list' => CompanyResource::getUrl('index'),
        'company view' => CompanyResource::getUrl('view', ['record' => $this->company]),
        'company edit' => CompanyResource::getUrl('edit', ['record' => $this->company]),
        'offers list' => OfferResource::getUrl('index'),
        'offer view' => OfferResource::getUrl('view', ['record' => $this->offer]),
        'offer create' => OfferResource::getUrl('create'),
        'scrape runs' => ScrapeRunResource::getUrl('index'),
    };

    $this->get($url)->assertOk();
})->with(['dashboard', 'companies list', 'company view', 'company edit', 'offers list', 'offer view', 'offer create', 'scrape runs']);
