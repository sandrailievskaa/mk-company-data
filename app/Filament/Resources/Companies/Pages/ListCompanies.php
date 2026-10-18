<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Services\ScrapeDispatcher;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Нова компанија'))
                ->icon(Heroicon::OutlinedPlus)
                ->color('gray')
                ->outlined(),
            Action::make('runCompanyScraper')
                ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                ->label(__('✦ Скрејпај компании'))
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('Скрејпај компании'))
                ->modalDescription(__('Се извршува автоматско собирање на податоци за компании. Процесот работи во позадина и може да потрае неколку минути.'))
                ->action(function (): void {
                    try {
                        $batch = app(ScrapeDispatcher::class)->dispatch();

                        Notification::make()
                            ->title(__('Скрејпањето е стартувано'))
                            ->body(__(':count сектори се ставени во ред. Прогресот е под „Скрејпања“ (потребен е активен queue worker).', ['count' => $batch->totalJobs]))
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title(__('Грешка при стартување'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('Компании');
    }
}
