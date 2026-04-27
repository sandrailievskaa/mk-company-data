<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum OfferStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Opened = 'opened';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Pending => 'Чека',
            self::Sent => 'Испратена',
            self::Failed => 'Неуспешна',
            self::Opened => 'Отворена',
        };
    }
}
