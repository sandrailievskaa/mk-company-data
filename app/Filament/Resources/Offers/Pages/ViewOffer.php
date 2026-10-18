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
        return __('Преглед');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->sendAction(),
            $this->scheduleAction(),
            $this->cancelScheduleAction(),
            EditAction::make()
                ->label(__('Уреди'))
                ->icon(Heroicon::OutlinedPencilSquare),
        ];
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        $offer = $this->getRecord();

        if ($offer->isScheduled()) {
            return __('Закажана за испраќање: :time', ['time' => $offer->scheduled_at->timezone(config('app.timezone'))->format('d.m.Y H:i')]);
        }

        return $offer->schedule_dispatched_at ? __('Испратена по распоред: :time', ['time' => $offer->schedule_dispatched_at->format('d.m.Y H:i')]) : null;
    }

    private function scheduleAction(): Action
    {
        return Action::make('schedule')
            ->label(__('Закажи испраќање'))
            ->icon(Heroicon::OutlinedClock)
            ->color('gray')
            ->visible(fn (): bool => ! $this->getRecord()->isScheduled())
            ->modalHeading(__('Закажи испраќање'))
            ->modalDescription(__('Понудата ќе се испрати автоматски во избраното време (потребен е активен scheduler: php artisan schedule:work). Претпоставените е-пошти се прескокнуваат.'))
            ->modalSubmitActionLabel(__('Закажи'))
            ->modalCancelActionLabel(__('Откажи'))
            ->schema([
                DateTimePicker::make('scheduled_at')
                    ->label(__('Време на испраќање'))
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

                Notification::make()->title(__('Испраќањето е закажано'))->success()->send();
                $this->refreshFormData(['scheduled_at']);
            });
    }

    private function cancelScheduleAction(): Action
    {
        return Action::make('cancelSchedule')
            ->label(__('Откажи закажување'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (): bool => $this->getRecord()->isScheduled())
            ->requiresConfirmation()
            ->action(function (): void {
                $this->getRecord()->update(['scheduled_at' => null]);

                Notification::make()->title(__('Закажувањето е откажано'))->send();
                $this->refreshFormData(['scheduled_at']);
            });
    }

    private function sendAction(): Action
    {
        return Action::make('send')
            ->label(__('Испрати понуда'))
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('primary')
            ->modalHeading(__('Испрати понуда'))
            ->modalSubmitActionLabel(__('Испрати'))
            ->modalCancelActionLabel(__('Откажи'))
            ->modalDescription(function (): HtmlString {
                $plan = app(SendOfferService::class)->plan($this->getRecord());
                $mailer = (string) config('mail.default');
                $testMode = in_array($mailer, ['log', 'array'], true);

                return new HtmlString(
                    '<p>'.e(__('Примачи: :recipients · ќе се испратат: :deliverable', [
                        'recipients' => $plan->recipients,
                        'deliverable' => $plan->deliverable(),
                    ])).'</p>'.
                    '<p>'.e(__('Веќе испратени (се прескокнуваат): :sent · без е-пошта: :noEmail · со претпоставена (непроверена) е-пошта: :inferred', [
                        'sent' => $plan->alreadySent,
                        'noEmail' => $plan->skippedNoEmail,
                        'inferred' => $plan->skippedInferred,
                    ])).'</p>'.
                    ($testMode
                        ? '<p><strong>'.e(__('Тест-режим:')).'</strong> '.e(__('пораките не се доставуваат на вистински адреси (mailer: :mailer), туку се запишуваат во storage/logs/laravel.log.', ['mailer' => $mailer])).'</p>'
                        : '')
                );
            })
            ->schema([
                Toggle::make('include_inferred')
                    ->label(__('Вклучи и претпоставени (непроверени) е-пошти'))
                    ->default(false),
            ])
            ->action(function (array $data): void {
                $report = app(SendOfferService::class)->send($this->getRecord(), (bool) ($data['include_inferred'] ?? false));

                Notification::make()
                    ->title(__('Испраќањето заврши'))
                    ->body(__('Испратени: :sent · неуспешни: :failed · веќе испратени: :already · без е-пошта: :noEmail · претпоставени (прескокнати): :inferred', [
                        'sent' => $report->sent,
                        'failed' => $report->failed,
                        'already' => $report->alreadySent,
                        'noEmail' => $report->skippedNoEmail,
                        'inferred' => $report->skippedInferred,
                    ]))
                    ->color($report->failed > 0 ? 'warning' : 'success')
                    ->send();

                $this->refreshFormData(['status']);
            });
    }
}
