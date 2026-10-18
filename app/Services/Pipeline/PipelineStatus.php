<?php

namespace App\Services\Pipeline;

use App\Enums\EmailSource;
use App\Enums\OfferStatus;
use App\Enums\PipelineTask;
use App\Models\Company;
use App\Models\Offer;
use App\Models\ScrapeRun;
use App\Models\SentOffer;

/**
 * Progress of every pipeline stage, for the pipeline overview page.
 */
class PipelineStatus
{
    public function __construct(private readonly PipelineDispatcher $dispatcher) {}

    /**
     * @return list<array{key: string, title: string, description: string, done: int, total: int, details: list<string>, pending: int|null}>
     */
    public function stages(): array
    {
        $total = Company::query()->count();
        $withEmail = Company::query()->whereNotNull('email')->count();
        $inferred = Company::query()->where('email_source', EmailSource::Inferred->value)->count();
        $lastRun = ScrapeRun::query()->latest('started_at')->first();

        $sent = SentOffer::query()->whereIn('status', [OfferStatus::Sent->value, OfferStatus::Opened->value])->count();

        return [
            [
                'key' => 'scrape',
                'title' => __('1. Собирање (zk.mk)'),
                'description' => __('Скрејпање по сектори; податоци: име, сектор, град, адреса, телефон.'),
                'done' => $total,
                'total' => $total,
                'details' => [
                    $lastRun ? __('Последно: :date (:sector)', ['date' => $lastRun->started_at?->format('d.m.Y H:i'), 'sector' => $lastRun->sector?->getLabel() ?? '—']) : __('Сè уште нема скрејпање'),
                ],
                'pending' => null,
            ],
            [
                'key' => 'clean',
                'title' => __('2. Чистење'),
                'description' => __('Нормализирани имиња; записите со исто нормализирано име се спојуваат.'),
                'done' => Company::query()->whereNotNull('normalized_name')->count(),
                'total' => $total,
                'details' => [],
                'pending' => null,
            ],
            [
                'key' => 'profiles',
                'title' => __('3. Профили (веб-сајт, социјални мрежи)'),
                'description' => __('Се чита профилот на секоја компанија на zk.mk.'),
                'done' => Company::query()->whereNotNull('profile_scraped_at')->count(),
                'total' => $total,
                'details' => [
                    __(':count со веб-сајт', ['count' => Company::query()->whereNotNull('website')->count()]),
                    __(':count со Facebook', ['count' => Company::query()->whereNotNull('facebook_url')->count()]),
                ],
                'pending' => $this->dispatcher->pending(PipelineTask::Profiles)->count(),
            ],
            [
                'key' => 'companywall',
                'title' => __('4. Регистарски податоци (CompanyWall)'),
                'description' => __('ЕДБ, ЕМБС, датум на основање, големина; се прифаќа само профил со исто име.'),
                'done' => Company::query()->whereNotNull('companywall_scraped_at')->count(),
                'total' => $total,
                'details' => [
                    __(':count пронајдени профили', ['count' => Company::query()->whereNotNull('companywall_url')->count()]),
                ],
                'pending' => $this->dispatcher->pending(PipelineTask::CompanyWall)->count(),
            ],
            [
                'key' => 'emails',
                'title' => __('5. Е-пошти'),
                'description' => __('Се читаат веб-сајтот и контакт-страниците. Претпоставените адреси се исклучени по подразбирање.'),
                'done' => $withEmail,
                'total' => $total,
                'details' => [
                    __(':count пронајдени', ['count' => $withEmail - $inferred]),
                    __(':count претпоставени (непроверени)', ['count' => $inferred]),
                ],
                'pending' => $this->dispatcher->pending(PipelineTask::Emails)->count(),
            ],
            [
                'key' => 'index',
                'title' => __('6. Индекс на активност'),
                'description' => __('0,5·E + 0,3·P + 0,2·F за секоја компанија.'),
                'done' => Company::query()->where('activity_index', '>', 0)->count(),
                'total' => $total,
                'details' => [],
                'pending' => null,
            ],
            [
                'key' => 'offers',
                'title' => __('7. Понуди'),
                'description' => __('AI-генерирана понуда, препораки, идемпотентно испраќање и следење.'),
                'done' => $sent,
                'total' => max($sent, Offer::query()->count()),
                'details' => [
                    __(':count подготвени понуди', ['count' => Offer::query()->count()]),
                    __(':count испратени пораки', ['count' => $sent]),
                ],
                'pending' => null,
            ],
        ];
    }
}
