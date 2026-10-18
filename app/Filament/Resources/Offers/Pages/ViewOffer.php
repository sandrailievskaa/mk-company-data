<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Filament\Resources\Offers\OfferResource;
use App\Services\Offers\SendOfferService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
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
            EditAction::make()
                ->label('Уреди')
                ->icon(Heroicon::OutlinedPencilSquare),
        ];
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
