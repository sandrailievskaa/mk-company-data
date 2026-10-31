<?php

use App\Filament\Widgets\GettingStartedWidget;
use App\Models\Company;
use App\Models\Offer;
use App\Models\User;
use App\Services\Onboarding\GettingStarted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('marks a step done once and ignores unknown steps', function () {
    $user = User::factory()->create();
    $guide = app(GettingStarted::class);

    $guide->complete($user, 'browse');
    $guide->complete($user, 'browse');
    $guide->complete($user, 'nonsense');

    expect($guide->doneCount($user->fresh()))->toBe(1)
        ->and($guide->isDone($user->fresh(), 'browse'))->toBeTrue()
        ->and($guide->isDone($user->fresh(), 'inspect'))->toBeFalse();
});

it('keeps progress per user', function () {
    [$a, $b] = User::factory()->count(2)->create();

    app(GettingStarted::class)->complete($a, 'offer');

    expect(app(GettingStarted::class)->doneCount($b->fresh()))->toBe(0);
});

it('completes steps by really using the panel', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Nova Gradba', 'sector' => 'gradezhni-kompanii', 'city' => 'Skopje', 'email' => 'a@b.mk']);
    $this->actingAs($user);

    $this->get('/admin/companies')->assertOk();
    expect(app(GettingStarted::class)->isDone($user->fresh(), 'browse'))->toBeTrue();

    $this->get('/admin/companies/'.$company->id)->assertOk();
    expect(app(GettingStarted::class)->isDone($user->fresh(), 'inspect'))->toBeTrue();
});

it('can be hidden and shown again from the dashboard widget', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(GettingStartedWidget::class)
        ->assertSee('Започни за 5 чекори')
        ->call('dismiss')
        ->assertSee('Покажи го водичот')
        ->assertDontSee('Започни за 5 чекори')
        ->call('restore')
        ->assertSee('Започни за 5 чекори');
});

it('shows the guide and the explainers in English', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Nova Gradba', 'sector' => 'gradezhni-kompanii', 'city' => 'Skopje', 'email' => 'a@b.mk']);
    $this->actingAs($user)->withSession(['locale' => 'en']);

    $this->get('/admin')->assertOk()
        ->assertSee('Get started in 5 steps')
        ->assertSee('Why?')
        ->assertSee('Guessed addresses (info@…) do not count.', false);

    $this->get('/admin/companies/'.$company->id)->assertOk()
        ->assertSee('A rule, not a predictive model.')
        ->assertSee('0.5·E + 0.3·P + 0.2·F');
});

it('shows the explainers in Macedonian by default', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Nova Gradba', 'sector' => 'gradezhni-kompanii', 'city' => 'Skopje', 'email' => 'a@b.mk']);
    $this->actingAs($user);

    $this->get('/admin/companies/'.$company->id)->assertOk()
        ->assertSee('Правило, не модел за предвидување.')
        ->assertSee('0,5·E + 0,3·P + 0,2·F');
});

it('completes the offer step after creating an offer and the track step after viewing a sent one', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Nova Gradba', 'sector' => 'gradezhni-kompanii', 'city' => 'Skopje', 'email' => 'a@b.mk']);
    $offer = Offer::create(['title' => 'Title', 'content' => 'Body', 'company_id' => $company->id]);
    $this->actingAs($user);

    $this->get('/admin/offers/'.$offer->id)->assertOk();
    expect(app(GettingStarted::class)->isDone($user->fresh(), 'track'))->toBeFalse();

    $offer->sentOffers()->create(['company_id' => $company->id, 'status' => 'sent', 'sent_at' => now()]);
    $this->get('/admin/offers/'.$offer->id)->assertOk();

    expect(app(GettingStarted::class)->isDone($user->fresh(), 'track'))->toBeTrue();
});

it('opens the video guide on first visit and remembers that it was seen', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(GettingStartedWidget::class)
        ->assertSee('Видео упатство')
        ->assertSee('open: true', false)
        ->call('markIntroSeen')
        ->assertSee('open: false', false);

    expect(app(GettingStarted::class)->isIntroSeen($user->fresh()))->toBeTrue();
});

it('keeps the video guide closed for users who already saw it', function () {
    $user = User::factory()->create();
    app(GettingStarted::class)->markIntroSeen($user);
    $this->actingAs($user->fresh());

    Livewire::test(GettingStartedWidget::class)->assertSee('open: false', false);
});

it('narrates the video guide in English', function () {
    $this->actingAs(User::factory()->create())->withSession(['locale' => 'en']);

    $this->get('/admin')->assertOk()
        ->assertSee('Video guide')
        ->assertSee('From public data to a ready offer')
        ->assertSee('At most once per company');
});
