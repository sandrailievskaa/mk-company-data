<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
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
                ->modalDescription('Се извршува автоматско собирање на податоци за компании. Процесот може да потрае неколку минути.')
                ->action(function (): void {
                    try {
                        if (function_exists('set_time_limit')) {
                            set_time_limit(0);
                        }

                        Artisan::call('app:scrape-companies-command');

                        Notification::make()
                            ->title('Скраперот заврши')
                            ->body('Податоците се освежени. Проверете ја листата.')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Грешка при скрапирање')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
