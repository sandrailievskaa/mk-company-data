<?php

use App\Enums\EmailSource;
use App\Enums\OfferStatus;
use App\Filament\Resources\Offers\Pages\ViewOffer;
use App\Mail\OfferMail;
use App\Models\Company;
use App\Models\Offer;
use App\Models\SentOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function scheduledOffer(?DateTimeInterface $at): Offer
{
    static $n = 0;
    $n++;
    $company = Company::create(['name' => "Real {$n}", 'email' => "office{$n}@real.mk", 'email_source' => EmailSource::Website]);

    return Offer::create(['title' => 'Понуда', 'content' => 'Текст', 'company_id' => $company->id, 'scheduled_at' => $at]);
}

it('sends only offers whose time has come, and only once', function () {
    Mail::fake();
    $due = scheduledOffer(now()->subMinute());
    $future = scheduledOffer(now()->addHour());
    $unscheduled = scheduledOffer(null);

    $this->artisan('app:send-scheduled-offers')->assertSuccessful();

    expect($due->fresh()->schedule_dispatched_at)->not->toBeNull()
        ->and($due->fresh()->status)->toBe(OfferStatus::Sent)
        ->and($future->fresh()->schedule_dispatched_at)->toBeNull()
        ->and($unscheduled->fresh()->schedule_dispatched_at)->toBeNull();
    Mail::assertSent(OfferMail::class, 1);

    // The scheduler runs every minute: a second run must not repeat anything.
    $this->artisan('app:send-scheduled-offers')->assertSuccessful();
    Mail::assertSent(OfferMail::class, 1);
    expect(SentOffer::count())->toBe(1);
});

it('does not repeat a failed scheduled offer on every tick', function () {
    $offer = scheduledOffer(now()->subMinute());
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP down'));

    $this->artisan('app:send-scheduled-offers')->assertSuccessful();
    $this->artisan('app:send-scheduled-offers')->assertSuccessful();

    expect($offer->fresh()->status)->toBe(OfferStatus::Failed);
});

it('schedules and cancels from the offer page', function () {
    $this->actingAs(User::factory()->create());
    $offer = scheduledOffer(null);
    $when = now()->addDay()->startOfMinute();

    Livewire::test(ViewOffer::class, ['record' => $offer->id])
        ->callAction('schedule', ['scheduled_at' => $when->toDateTimeString()])
        ->assertNotified('Испраќањето е закажано');

    expect($offer->fresh()->isScheduled())->toBeTrue();

    Livewire::test(ViewOffer::class, ['record' => $offer->id])
        ->assertActionHidden('schedule')
        ->callAction('cancelSchedule');

    expect($offer->fresh()->isScheduled())->toBeFalse();
});

it('schedules the scheduler command every minute', function () {
    $events = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
        ->filter(fn ($e) => str_contains($e->command, 'app:send-scheduled-offers'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('* * * * *');
});
