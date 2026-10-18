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
                'title' => '1. Собирање (zk.mk)',
                'description' => 'Скрејпинг по сектори; податоци: име, сектор, град, адреса, телефон.',
                'done' => $total,
                'total' => $total,
                'details' => [
                    $lastRun ? 'Последно: '.$lastRun->started_at?->format('d.m.Y H:i').' ('.($lastRun->sector?->getLabel() ?? '—').')' : 'Сè уште нема скрејпање',
                ],
                'pending' => null,
            ],
            [
                'key' => 'clean',
                'title' => '2. Чистење',
                'description' => 'Нормализирани имиња; записите со исто нормализирано име се спојуваат.',
                'done' => Company::query()->whereNotNull('normalized_name')->count(),
                'total' => $total,
                'details' => [],
                'pending' => null,
            ],
            [
                'key' => 'profiles',
                'title' => '3. Профили (веб-сајт, социјални мрежи)',
                'description' => 'Се чита профилот на секоја компанија на zk.mk.',
                'done' => Company::query()->whereNotNull('profile_scraped_at')->count(),
                'total' => $total,
                'details' => [
                    Company::query()->whereNotNull('website')->count().' со веб-сајт',
                    Company::query()->whereNotNull('facebook_url')->count().' со Facebook',
                ],
                'pending' => $this->dispatcher->pending(PipelineTask::Profiles)->count(),
            ],
            [
                'key' => 'companywall',
                'title' => '4. Регистарски податоци (CompanyWall)',
                'description' => 'ЕДБ, ЕМБС, датум на основање, големина; се прифаќа само профил со исто име.',
                'done' => Company::query()->whereNotNull('companywall_scraped_at')->count(),
                'total' => $total,
                'details' => [
                    Company::query()->whereNotNull('companywall_url')->count().' пронајдени профили',
                ],
                'pending' => $this->dispatcher->pending(PipelineTask::CompanyWall)->count(),
            ],
            [
                'key' => 'emails',
                'title' => '5. Е-пошти',
                'description' => 'Се читаат веб-сајтот и контакт-страниците. Претпоставените адреси се исклучени по подразбирање.',
                'done' => $withEmail,
                'total' => $total,
                'details' => [
                    ($withEmail - $inferred).' пронајдени',
                    $inferred.' претпоставени (непроверени)',
                ],
                'pending' => $this->dispatcher->pending(PipelineTask::Emails)->count(),
            ],
            [
                'key' => 'index',
                'title' => '6. Индекс на активност',
                'description' => '0,5·E + 0,3·P + 0,2·F за секоја компанија.',
                'done' => Company::query()->where('activity_index', '>', 0)->count(),
                'total' => $total,
                'details' => [],
                'pending' => null,
            ],
            [
                'key' => 'offers',
                'title' => '7. Понуди',
                'description' => 'ВИ-генерирана понуда, препораки, идемпотентно испраќање и следење.',
                'done' => $sent,
                'total' => max($sent, Offer::query()->count()),
                'details' => [
                    Offer::query()->count().' подготвени понуди',
                    $sent.' испратени порaки',
                ],
                'pending' => null,
            ],
        ];
    }
}
