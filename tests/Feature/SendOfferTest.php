<?php

use App\Enums\EmailSource;
use App\Enums\OfferStatus;
use App\Mail\OfferMail;
use App\Models\Company;
use App\Models\Offer;
use App\Models\OfferTarget;
use App\Models\SentOffer;
use App\Services\Offers\SendOfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function offerWithRecipients(): Offer
{
    $real = Company::create(['name' => 'Real', 'email' => 'office@real.mk', 'email_source' => EmailSource::Website]);
    $guessed = Company::create(['name' => 'Guessed', 'email' => 'info@guessed.mk', 'email_source' => EmailSource::Inferred]);
    $none = Company::create(['name' => 'None']);

    $offer = Offer::create(['title' => 'Понуда', 'content' => "Прв пасус.\n\nВтор пасус.", 'company_id' => $real->id]);
    OfferTarget::create(['offer_id' => $offer->id, 'company_id' => $guessed->id]);
    OfferTarget::create(['offer_id' => $offer->id, 'company_id' => $none->id]);

    return $offer;
}

it('plans without sending anything', function () {
    Mail::fake();
    $offer = offerWithRecipients();

    $plan = app(SendOfferService::class)->plan($offer);

    expect($plan->recipients)->toBe(3)
        ->and($plan->deliverable())->toBe(1)
        ->and($plan->skippedInferred)->toBe(1)
        ->and($plan->skippedNoEmail)->toBe(1);
    Mail::assertNothingSent();
    expect(SentOffer::count())->toBe(0);
});

it('sends only to discovered addresses by default and records the status', function () {
    Mail::fake();
    $offer = offerWithRecipients();

    $report = app(SendOfferService::class)->send($offer);

    expect($report->sent)->toBe(1)->and($report->failed)->toBe(0);
    Mail::assertSent(OfferMail::class, 1);

    $sent = SentOffer::first();
    expect($sent->status)->toBe(OfferStatus::Sent)
        ->and($sent->recipient_email)->toBe('office@real.mk')
        ->and($sent->sent_at)->not->toBeNull()
        ->and($offer->fresh()->status)->toBe(OfferStatus::Sent);
});

it('can include inferred addresses when asked', function () {
    Mail::fake();
    $offer = offerWithRecipients();

    $report = app(SendOfferService::class)->send($offer, includeInferred: true);

    expect($report->sent)->toBe(2);
    Mail::assertSent(OfferMail::class, 2);
});

it('never sends the same offer twice to the same company', function () {
    Mail::fake();
    $offer = offerWithRecipients();
    $service = app(SendOfferService::class);

    $service->send($offer);
    $second = $service->send($offer);

    expect($second->sent)->toBe(0)
        ->and($second->alreadySent)->toBe(1);
    Mail::assertSent(OfferMail::class, 1);
    expect(SentOffer::count())->toBe(1);
});

it('records failures and retries them', function () {
    $offer = offerWithRecipients();
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP down'));

    $report = app(SendOfferService::class)->send($offer);

    expect($report->failed)->toBe(1);
    $sent = SentOffer::first();
    expect($sent->status)->toBe(OfferStatus::Failed)
        ->and($sent->error_message)->toBe('SMTP down')
        ->and($offer->fresh()->status)->toBe(OfferStatus::Failed);

    Mail::swap(new Illuminate\Mail\MailManager(app()));
    Mail::fake();

    expect(app(SendOfferService::class)->retry($sent))->toBeTrue()
        ->and($sent->fresh()->status)->toBe(OfferStatus::Sent)
        ->and($sent->fresh()->error_message)->toBeNull();
});

it('marks the delivery as opened through the tracking pixel, once', function () {
    Mail::fake();
    $offer = offerWithRecipients();
    app(SendOfferService::class)->send($offer);
    $sent = SentOffer::first();

    $this->get(route('offers.track', $sent->token))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/gif');

    $openedAt = $sent->fresh()->opened_at;
    expect($sent->fresh()->status)->toBe(OfferStatus::Opened)
        ->and($openedAt)->not->toBeNull()
        ->and($offer->fresh()->status)->toBe(OfferStatus::Opened);

    $this->get(route('offers.track', $sent->token))->assertOk();
    expect($sent->fresh()->opened_at->equalTo($openedAt))->toBeTrue();
});

it('answers unknown tracking tokens with the pixel and changes nothing', function () {
    $this->get(route('offers.track', str_repeat('a', 40)))->assertOk();

    expect(SentOffer::count())->toBe(0);
});

it('renders paragraphs and the tracking pixel in the mail', function () {
    $offer = offerWithRecipients();
    $sent = SentOffer::create(['offer_id' => $offer->id, 'company_id' => $offer->company_id]);

    $html = (new OfferMail($offer, $sent))->render();

    expect($html)->toContain('Прв пасус.')->toContain('Втор пасус.')->toContain($sent->token);
});

it('sends from the offer page action and shows the result', function () {
    Mail::fake();
    $this->actingAs(App\Models\User::factory()->create());
    $offer = offerWithRecipients();

    Livewire\Livewire::test(App\Filament\Resources\Offers\Pages\ViewOffer::class, ['record' => $offer->id])
        ->callAction('send', ['include_inferred' => false])
        ->assertNotified('Испраќањето заврши');

    Mail::assertSent(OfferMail::class, 1);
    expect($offer->fresh()->status)->toBe(OfferStatus::Sent);
});

it('lists deliveries in the offer relation manager', function () {
    Mail::fake();
    $this->actingAs(App\Models\User::factory()->create());
    $offer = offerWithRecipients();
    app(SendOfferService::class)->send($offer);

    Livewire\Livewire::test(App\Filament\Resources\Offers\RelationManagers\SentOffersRelationManager::class, [
        'ownerRecord' => $offer,
        'pageClass' => App\Filament\Resources\Offers\Pages\ViewOffer::class,
    ])->assertCanSeeTableRecords($offer->sentOffers);
});
