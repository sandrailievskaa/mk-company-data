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
            self::CONSTRUCTION => 'Градежен',
            self::PROGRAMMING => 'ИТ',
            self::HEALTHCARE => 'Здравство',
            self::TRAVELAGENCIES => 'Туризам',
            self::BANKS => 'Банкарство',
            self::MUNICIPALITIES => 'Општини',
            self::EDUCATION => 'Образование',
            self::AIRPLANE => 'Авионски',
            self::INSURANCE => 'Осигурување',
            self::FINANCE => 'Финансии',
        };
    }
}
