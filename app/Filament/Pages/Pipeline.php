<?php

namespace App\Filament\Pages;

use App\Enums\PipelineTask;
use App\Services\ActivityIndex\ActivityIndexCalculator;
use App\Services\Pipeline\PipelineDispatcher;
use App\Services\Pipeline\PipelineStatus;
use App\Services\ScrapeDispatcher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;

class Pipeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Пајплајн';

    protected static ?string $title = 'Пајплајн за податоци';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.pipeline';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'stages' => app(PipelineStatus::class)->stages(),
            'queue' => config('queue.default'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->queueAction('scrape', 'Скрејпај сектори', fn () => app(ScrapeDispatcher::class)->dispatch()),
            $this->taskAction(PipelineTask::Profiles, 'Прочитај профили'),
            $this->taskAction(PipelineTask::CompanyWall, 'CompanyWall'),
            $this->taskAction(PipelineTask::Emails, 'Пронајди е-пошти'),
            Action::make('recalculate')
                ->label('Пресметај индекс')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $stats = app(ActivityIndexCalculator::class)->recalculateAll();

                    Notification::make()->title('Индексот е пресметан')->body($stats['count'].' компании')->success()->send();
                }),
        ];
    }

    private function taskAction(PipelineTask $task, string $label): Action
    {
        return $this->queueAction($task->value, $label, fn () => app(PipelineDispatcher::class)->dispatch($task), $task->label());
    }

    private function queueAction(string $name, string $label, \Closure $dispatch, ?string $description = null): Action
    {
        return Action::make($name)
            ->label($label)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(($description ? $description.'. ' : '').'Работи во позадина; потребен е активен queue worker (php artisan queue:work).')
            ->action(function () use ($dispatch): void {
                try {
                    $batch = $dispatch();

                    Notification::make()
                        ->title($batch ? 'Ставено во ред' : 'Нема што да се обработи')
                        ->body($batch ? $batch->totalJobs.' задачи. Прогресот се освежува на оваа страница.' : null)
                        ->color($batch ? 'success' : 'gray')
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()->title('Грешка')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
