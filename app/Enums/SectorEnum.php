<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum SectorEnum: string implements HasLabel
{
    case CONSTRUCTION = 'gradezhni-kompanii';
    case PROGRAMMING = 'programiranje';
    case HEALTHCARE = 'zdravstvo';
    case TRAVELAGENCIES = 'turistichki-agencii';
    case BANKS = 'banki?filter_dejnost=5256705811c9692dd7cba247';
    case MUNICIPALITIES = 'opshtini';
    case EDUCATION = 'obrazovanie';
    case AIRPLANE = 'aviokompanii';
    case INSURANCE = 'osiguruvanje-i-osiguritelni-kompanii';
    case FINANCE = 'finansiski-kompanii-i-institucii';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::CONSTRUCTION => __('Градежен'),
            self::PROGRAMMING => __('ИТ'),
            self::HEALTHCARE => __('Здравство'),
            self::TRAVELAGENCIES => __('Туризам'),
            self::BANKS => __('Банкарство'),
            self::MUNICIPALITIES => __('Општини'),
            self::EDUCATION => __('Образование'),
            self::AIRPLANE => __('Авионски'),
            self::INSURANCE => __('Осигурување'),
            self::FINANCE => __('Финансии'),
        };
    }
}
