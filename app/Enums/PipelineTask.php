<?php

namespace App\Enums;

/**
 * Per-company pipeline stages that run in the background after the registry scrape.
 */
enum PipelineTask: string
{
    case Profiles = 'profiles';
    case CompanyWall = 'companywall';
    case Emails = 'emails';

    public function label(): string
    {
        return match ($this) {
            self::Profiles => __('zk.mk профили (веб-сајт, социјални мрежи)'),
            self::CompanyWall => __('CompanyWall (ЕДБ, ЕМБС, големина)'),
            self::Emails => __('Е-пошти (веб-сајт, пребарување)'),
        };
    }
}
