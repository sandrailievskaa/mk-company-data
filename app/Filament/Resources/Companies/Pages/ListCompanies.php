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

    protected static ?string $title = 'Компании';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Нова компанија')
                ->icon(Heroicon::OutlinedPlus)
                ->color('gray')
                ->outlined(),
            Action::make('runCompanyScraper')
                ->label('✦ Скрејпај компании')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Скрејпај компании')
                ->modalDescription('Се извршува автоматско собирање на податоци за компании. Процесот работи во позадина и може да потрае неколку минути.')
                ->action(function (): void {
                    try {
                        $batch = app(ScrapeDispatcher::class)->dispatch();

                        Notification::make()
                            ->title('Скрејпањето е стартувано')
                            ->body($batch->totalJobs.' сектори се ставени во ред. Прогресот е под „Скрејпања“ (потребен е активен queue worker).')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Грешка при стартување')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
