<?php

use App\Models\Company;
use App\Models\User;
use App\Services\DashboardBreakdownService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function breakdownCompany(array $attrs): Company
{
    static $n = 0;
    $n++;

    return Company::create(array_merge([
        'name' => 'Firma '.$n,
        'normalized_name' => 'firma'.$n,
        'sector' => 'programiranje',
        'city' => 'Skopje',
    ], $attrs));
}

it('computes e-mail coverage per sector and ignores guessed addresses', function () {
    breakdownCompany(['email' => 'a@a.mk', 'email_source' => 'website']);
    breakdownCompany(['email' => 'b@b.mk', 'email_source' => 'inferred']);
    breakdownCompany([]);
    breakdownCompany(['sector' => 'zdravstvo', 'email' => 'c@c.mk', 'email_source' => 'website']);

    $sectors = collect(app(DashboardBreakdownService::class)->sectors())->keyBy('label');

    $it = $sectors[(string) \App\Enums\SectorEnum::PROGRAMMING->getLabel()];
    expect($it['count'])->toBe(3)
        ->and($it['withEmail'])->toBe(1)
        ->and($it['coverage'])->toBe(33.3)
        ->and($it['small'])->toBeTrue();
});

it('builds a funnel from registry to verified contact', function () {
    breakdownCompany(['website' => 'https://a.mk', 'email' => 'a@a.mk', 'email_source' => 'website', 'email_domain_has_mx' => true]);
    breakdownCompany(['website' => 'https://b.mk', 'email' => 'b@b.mk', 'email_source' => 'website', 'email_domain_has_mx' => false]);
    breakdownCompany(['website' => 'https://c.mk']);
    breakdownCompany(['email' => 'd@d.mk', 'email_source' => 'inferred']);

    $funnel = app(DashboardBreakdownService::class)->funnel();
    $counts = collect($funnel['steps'])->pluck('count', 'key')->all();

    expect($counts)->toBe(['total' => 4, 'website' => 3, 'email' => 2, 'mx' => 1])
        ->and($funnel['inferred'])->toBe(1);
});

it('shows both charts on the dashboard in both languages', function () {
    breakdownCompany(['website' => 'https://a.mk', 'email' => 'a@a.mk', 'email_source' => 'website', 'email_domain_has_mx' => true]);
    $this->actingAs(User::factory()->create());

    $this->get('/admin')->assertOk()
        ->assertSee('Покриеност на е-пошта по сектор')
        ->assertSee('Од компанија до употреблив контакт');

    $this->withSession(['locale' => 'en'])->get('/admin')->assertOk()
        ->assertSee('E-mail coverage by sector')
        ->assertSee('From company to usable contact');
});

it('shows the login page tagline and points', function () {
    $this->get('/admin/login')->assertOk()
        ->assertSee('Од јавни податоци до подготвена понуда.')
        ->assertSee('Потекло на секоја е-пошта, без претпоставки');

    $this->withSession(['locale' => 'en'])->get('/admin/login')->assertOk()
        ->assertSee('Source of every e-mail, no guesses');
});
