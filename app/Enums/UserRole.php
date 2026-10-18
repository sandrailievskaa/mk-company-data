<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum UserRole: string implements HasLabel
{
    /** Full access: data pipeline, deletions, offers. */
    case Admin = 'admin';

    /** Works with companies and offers; cannot run the pipeline or delete data. */
    case Operator = 'operator';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Admin => __('Администратор'),
            self::Operator => __('Оператор'),
        };
    }
}
