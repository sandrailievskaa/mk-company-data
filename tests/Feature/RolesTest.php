<?php

use App\Enums\UserRole;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\ScrapeRuns\ScrapeRunResource;
use App\Models\Company;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function userWithRole(UserRole $role): User
{
    return User::factory()->create(['role' => $role]);
}

it('treats new users as administrators by default', function () {
    expect(User::factory()->create()->fresh()->isAdmin())->toBeTrue();
});

it('lets administrators open the pipeline and scrape history', function () {
    $this->actingAs(userWithRole(UserRole::Admin));

    $this->get('/admin/pipeline')->assertOk();
    $this->get(ScrapeRunResource::getUrl('index'))->assertOk();
});

it('keeps operators out of the pipeline and scrape history', function () {
    $this->actingAs(userWithRole(UserRole::Operator));

    $this->get('/admin/pipeline')->assertForbidden();
    $this->get(ScrapeRunResource::getUrl('index'))->assertForbidden();
});

it('lets operators work with companies and offers', function () {
    $this->actingAs(userWithRole(UserRole::Operator));
    $company = Company::create(['name' => 'Фирма']);
    $offer = Offer::create(['title' => 'T', 'content' => 'C', 'company_id' => $company->id]);

    $this->get('/admin/companies')->assertOk();
    $this->get('/admin/offers')->assertOk();
    $this->get('/admin/offers/'.$offer->id)->assertOk();
});

it('only lets administrators delete companies and offers', function () {
    $company = Company::create(['name' => 'Фирма']);
    $offer = Offer::create(['title' => 'T', 'content' => 'C', 'company_id' => $company->id]);

    $operator = userWithRole(UserRole::Operator);
    $admin = userWithRole(UserRole::Admin);

    expect($operator->can('delete', $company))->toBeFalse()
        ->and($operator->can('deleteAny', Company::class))->toBeFalse()
        ->and($operator->can('delete', $offer))->toBeFalse()
        ->and($operator->can('update', $company))->toBeTrue()
        ->and($admin->can('delete', $company))->toBeTrue()
        ->and($admin->can('delete', $offer))->toBeTrue();
});

it('hides the scrape button from operators', function () {
    $this->actingAs(userWithRole(UserRole::Operator));
    Livewire::test(ListCompanies::class)->assertActionHidden('runCompanyScraper');

    $this->actingAs(userWithRole(UserRole::Admin));
    Livewire::test(ListCompanies::class)->assertActionVisible('runCompanyScraper');
});

it('changes a role from the command and protects the last administrator', function () {
    $admin = User::factory()->create(['email' => 'only@admin.mk']);

    $this->artisan('app:set-user-role only@admin.mk operator')->assertFailed();
    expect($admin->fresh()->isAdmin())->toBeTrue();

    $other = User::factory()->create(['email' => 'second@admin.mk']);
    $this->artisan('app:set-user-role second@admin.mk operator')->assertSuccessful();
    expect($other->fresh()->role)->toBe(UserRole::Operator);

    $this->artisan('app:set-user-role nobody@x.mk admin')->assertFailed();
    $this->artisan('app:set-user-role second@admin.mk boss')->assertFailed();
});
