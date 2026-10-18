<?php

use App\Enums\EmailSource;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.api.token' => 'secret-token']);
    $this->withHeaders(['Authorization' => 'Bearer secret-token']);
});

it('is disabled until a token is configured', function () {
    config(['services.api.token' => null]);

    $this->getJson('/api/companies')->assertStatus(503);
});

it('rejects missing and wrong tokens', function () {
    $this->withHeaders(['Authorization' => ''])->getJson('/api/companies')->assertUnauthorized();
    $this->withHeaders(['Authorization' => 'Bearer nope'])->getJson('/api/companies')->assertUnauthorized();
});

it('lists companies by activity index with the origin of every contact', function () {
    Company::create(['name' => 'Ниска', 'activity_index' => 0.1]);
    Company::create([
        'name' => 'Висока', 'sector' => 'programiranje', 'city' => ' Скопје', 'activity_index' => 0.8,
        'email' => 'info@visoka.mk', 'email_source' => EmailSource::Inferred, 'website' => 'http://visoka.mk',
    ]);

    $this->getJson('/api/companies')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Висока')
        ->assertJsonPath('data.0.sector_label', 'ИТ')
        ->assertJsonPath('data.0.city', 'Скопје')
        ->assertJsonPath('data.0.email_source', 'inferred')
        ->assertJsonPath('data.0.email_is_inferred', true)
        ->assertJsonPath('data.1.name', 'Ниска')
        ->assertJsonPath('meta.total', 2);
});

it('filters by sector, city, contact data and minimum index', function () {
    Company::create(['name' => 'А', 'sector' => 'programiranje', 'city' => 'Скопје', 'email' => 'a@a.mk', 'activity_index' => 0.6]);
    Company::create(['name' => 'Б', 'sector' => 'programiranje', 'city' => 'Битола', 'activity_index' => 0.2]);
    Company::create(['name' => 'В', 'sector' => 'zdravstvo', 'city' => 'Скопје', 'activity_index' => 0.9]);

    $names = fn (string $query) => collect($this->getJson('/api/companies?'.$query)->assertOk()->json('data'))->pluck('name')->sort()->values()->all();

    expect($names('sector=programiranje'))->toBe(['А', 'Б'])
        ->and($names('sector=programiranje&city=Скопје'))->toBe(['А'])
        ->and($names('has_email=1'))->toBe(['А'])
        ->and($names('has_email=0'))->toBe(['Б', 'В'])
        ->and($names('min_index=0.5'))->toBe(['А', 'В'])
        ->and($names('q=Б'))->toBe(['Б']);
});

it('validates filters and caps the page size', function () {
    $this->getJson('/api/companies?sector=nonsense')->assertUnprocessable();
    $this->getJson('/api/companies?min_index=2')->assertUnprocessable();
    $this->getJson('/api/companies?per_page=1000')->assertUnprocessable();
});

it('shows a single company and 404s for unknown ids', function () {
    $company = Company::create(['name' => 'Фирма', 'edb' => '4030000000000']);

    $this->getJson('/api/companies/'.$company->id)->assertOk()->assertJsonPath('data.edb', '4030000000000');
    $this->getJson('/api/companies/999999')->assertNotFound();
});

it('is read-only', function () {
    $this->postJson('/api/companies', ['name' => 'X'])->assertStatus(405);
    $this->deleteJson('/api/companies/1')->assertStatus(405);
});
