<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum SectorEnum: string implements HasLabel
{
    case CONSTRUCTION = 'gradezhni-kompanii';
    case PROGRAMMING = 'programiranje';
    case HEALTHCARE = 'zdravstvo';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::CONSTRUCTION => 'Градежни компании',
            self::PROGRAMMING => 'Програмирање',
            self::HEALTHCARE => 'Здравство',
        };
    }
}
