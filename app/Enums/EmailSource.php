<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Provenance of a company's e-mail address.
 *
 * Inferred addresses (pattern-generated, e.g. info@domain.mk) are never verified
 * and must not be treated as reliably discovered contacts.
 */
enum EmailSource: string implements HasColor, HasLabel
{
    case Registry = 'registry';
    case Website = 'website';
    case Search = 'search';
    case Inferred = 'inferred';
    case Manual = 'manual';
    /** Legacy rows: found by scraping/enrichment, exact origin was not recorded. */
    case Observed = 'observed';

    public function isInferred(): bool
    {
        return $this === self::Inferred;
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Registry => 'Регистар',
            self::Website => 'Веб-сајт',
            self::Search => 'Пребарување',
            self::Inferred => 'Претпоставена',
            self::Manual => 'Рачно',
            self::Observed => 'Пронајдена',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Inferred => 'warning',
            self::Manual => 'info',
            default => 'success',
        };
    }
}
