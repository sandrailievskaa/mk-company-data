<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Offers\OfferResource;
use App\Models\Company;
use App\Services\ActivityIndex\ActivityIndexCalculator;
use App\Services\Onboarding\GettingStarted;
use Filament\Widgets\Widget;

class GettingStartedWidget extends Widget
{
    protected string $view = 'filament.widgets.getting-started';

    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function dismiss(): void
    {
        if ($user = auth()->user()) {
            app(GettingStarted::class)->dismiss($user);
        }
    }

    public function markIntroSeen(): void
    {
        if ($user = auth()->user()) {
            app(GettingStarted::class)->markIntroSeen($user);
        }
    }

    public function restore(): void
    {
        if ($user = auth()->user()) {
            app(GettingStarted::class)->restore($user);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $guide = app(GettingStarted::class);
        $user = auth()->user();

        $topCompany = Company::query()->orderByDesc('activity_index')->first();
        $calculator = app(ActivityIndexCalculator::class);
        $scale = $calculator->frequencyScale();
        $showcase = Company::query()
            ->whereNotNull('email')
            ->orderByDesc('activity_index')
            ->limit(3)
            ->get()
            ->map(fn (Company $c): array => [
                'name' => (string) $c->name,
                'city' => (string) $c->city,
                'pct' => (int) round(max(0, min(1, (float) $c->activity_index)) * 100),
                'parts' => $calculator->components($c, $scale),
            ])
            ->all();

        // A real recording can replace the animation: put it at public/videos/user-guide-mk.mp4 (and -en.mp4).
        $videoFile = 'videos/user-guide-'.app()->getLocale().'.mp4';

        $steps = [
            'browse' => [
                'title' => __('Најди компании'),
                'text' => __('Пребарај и филтрирај по сектор, град или е-пошта.'),
                'url' => CompanyResource::getUrl('index'),
            ],
            'inspect' => [
                'title' => __('Провери од каде е податокот'),
                'text' => __('Отвори компанија: индексот, неговите делови и потеклото на е-поштата.'),
                'url' => $topCompany ? CompanyResource::getUrl('view', ['record' => $topCompany]) : CompanyResource::getUrl('index'),
            ],
            'offer' => [
                'title' => __('Создај AI понуда'),
                'text' => __('Внеси контекст, генерирај текст и препорачај компании.'),
                'url' => OfferResource::getUrl('create'),
            ],
            'send' => [
                'title' => __('Испрати понуда'),
                'text' => __('Секоја компанија ја добива најмногу еднаш. Во пробен режим пораките одат во лог.'),
                'url' => OfferResource::getUrl('index'),
            ],
            'track' => [
                'title' => __('Следи статуси'),
                'text' => __('Чека, испратена, неуспешна или отворена.'),
                'url' => OfferResource::getUrl('index'),
            ],
        ];

        $next = null;
        foreach ($steps as $key => &$step) {
            $step['done'] = $guide->isDone($user, $key);
            if (! $step['done'] && $next === null) {
                $next = $key;
            }
        }
        unset($step);

        return [
            'steps' => $steps,
            'next' => $next,
            'done' => $guide->doneCount($user),
            'total' => count(GettingStarted::STEPS),
            'dismissed' => $guide->isDismissed($user),
            'introSeen' => $guide->isIntroSeen($user),
            'videoUrl' => is_file(public_path($videoFile)) ? asset($videoFile) : null,
            'showcase' => $showcase,
            'weights' => config('activity_index.weights'),
            'companyCount' => Company::query()->count(),
        ];
    }
}
