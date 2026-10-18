<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum OfferStatus: string implements HasColor, HasLabel
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

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Sent => 'success',
            self::Failed => 'danger',
            self::Opened => 'info',
        };
    }

    /** Delivered to the recipient (sent, and possibly opened). */
    public function isDelivered(): bool
    {
        return $this === self::Sent || $this === self::Opened;
    }
}
