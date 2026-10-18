<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Filament\Resources\Offers\OfferResource;
use App\Services\Offers\SendOfferService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ViewOffer extends ViewRecord
{
    protected static string $resource = OfferResource::class;

    public function getPageClasses(): array
    {
        return array_merge(parent::getPageClasses(), [
            'mk-offer-view',
        ]);
    }

    public function getTitle(): string
    {
        return (string) $this->getRecord()->title;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Преглед';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->sendAction(),
            $this->scheduleAction(),
            $this->cancelScheduleAction(),
            EditAction::make()
                ->label('Уреди')
                ->icon(Heroicon::OutlinedPencilSquare),
        ];
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        $offer = $this->getRecord();

        if ($offer->isScheduled()) {
            return 'Закажана за испраќање: '.$offer->scheduled_at->timezone(config('app.timezone'))->format('d.m.Y H:i');
        }

        return $offer->schedule_dispatched_at ? 'Испратена по распоред: '.$offer->schedule_dispatched_at->format('d.m.Y H:i') : null;
    }

    private function scheduleAction(): Action
    {
        return Action::make('schedule')
            ->label('Закажи испраќање')
            ->icon(Heroicon::OutlinedClock)
            ->color('gray')
            ->visible(fn (): bool => ! $this->getRecord()->isScheduled())
            ->modalHeading('Закажи испраќање')
            ->modalDescription('Понудата ќе се испрати автоматски во избраното време (потребен е активен scheduler: php artisan schedule:work). Претпоставените е-пошти се прескокнуваат.')
            ->modalSubmitActionLabel('Закажи')
            ->modalCancelActionLabel('Откажи')
            ->schema([
                DateTimePicker::make('scheduled_at')
                    ->label('Време на испраќање')
                    ->required()
                    ->seconds(false)
                    ->minDate(now())
                    ->native(false),
            ])
            ->action(function (array $data): void {
                $this->getRecord()->update([
                    'scheduled_at' => $data['scheduled_at'],
                    'schedule_dispatched_at' => null,
                ]);

                Notification::make()->title('Испраќањето е закажано')->success()->send();
                $this->refreshFormData(['scheduled_at']);
            });
    }

    private function cancelScheduleAction(): Action
    {
        return Action::make('cancelSchedule')
            ->label('Откажи закажување')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (): bool => $this->getRecord()->isScheduled())
            ->requiresConfirmation()
            ->action(function (): void {
                $this->getRecord()->update(['scheduled_at' => null]);

                Notification::make()->title('Закажувањето е откажано')->send();
                $this->refreshFormData(['scheduled_at']);
            });
    }

    private function sendAction(): Action
    {
        return Action::make('send')
            ->label('Испрати понуда')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('primary')
            ->modalHeading('Испрати понуда')
            ->modalSubmitActionLabel('Испрати')
            ->modalCancelActionLabel('Откажи')
            ->modalDescription(function (): HtmlString {
                $plan = app(SendOfferService::class)->plan($this->getRecord());
                $mailer = (string) config('mail.default');
                $testMode = in_array($mailer, ['log', 'array'], true);

                return new HtmlString(
                    '<p>Примачи: <strong>'.$plan->recipients.'</strong> · ќе се испратат: <strong>'.$plan->deliverable().'</strong></p>'.
                    '<p>Веќе испратени (се прескокнуваат): '.$plan->alreadySent.' · без е-пошта: '.$plan->skippedNoEmail.
                    ' · со претпоставена (непроверена) е-пошта: '.$plan->skippedInferred.'</p>'.
                    ($testMode
                        ? '<p><strong>Тест-режим:</strong> пораките не се доставуваат на вистински адреси (mailer: '.e($mailer).'), туку се запишуваат во storage/logs/laravel.log.</p>'
                        : '')
                );
            })
            ->schema([
                Toggle::make('include_inferred')
                    ->label('Вклучи и претпоставени (непроверени) е-пошти')
                    ->default(false),
            ])
            ->action(function (array $data): void {
                $report = app(SendOfferService::class)->send($this->getRecord(), (bool) ($data['include_inferred'] ?? false));

                Notification::make()
                    ->title('Испраќањето заврши')
                    ->body(sprintf(
                        'Испратени: %d · неуспешни: %d · веќе испратени: %d · без е-пошта: %d · претпоставени (прескокнати): %d',
                        $report->sent, $report->failed, $report->alreadySent, $report->skippedNoEmail, $report->skippedInferred
                    ))
                    ->color($report->failed > 0 ? 'warning' : 'success')
                    ->send();

                $this->refreshFormData(['status']);
            });
    }
}
