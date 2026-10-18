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
        'website' => 'http://novagradba.mk', 'facebook_url' => 'https://facebook.com/novagradba', 'edb' => '4030000000000',
        'companywall_url' => '/kompanija/x/Y', 'email_domain_has_mx' => true,
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
        'pipeline' => '/admin/pipeline',
    };

    $this->get($url)->assertOk();
})->with(['dashboard', 'companies list', 'company view', 'company edit', 'offers list', 'offer view', 'offer create', 'scrape runs', 'pipeline']);

it('filters companies by email presence and quality flag', function () {
    $with = Company::create(['name' => 'Со пошта', 'email' => 'a@b.mk', 'email_source' => EmailSource::Website]);
    $without = Company::create(['name' => 'Без пошта', 'data_quality_flag' => 'duplicate']);

    $list = Livewire\Livewire::test(App\Filament\Resources\Companies\Pages\ListCompanies::class);

    $list->filterTable('has_email', true)->assertCanSeeTableRecords([$with])->assertCanNotSeeTableRecords([$without]);
    $list->filterTable('has_email', false)->assertCanSeeTableRecords([$without])->assertCanNotSeeTableRecords([$with]);
    $list->removeTableFilters()->filterTable('data_quality_flag', 'duplicate')->assertCanSeeTableRecords([$without]);
});

it('shows offer campaign statistics on the dashboard', function () {
    $this->get('/admin')->assertOk()->assertSee('Подготвени понуди');
});
