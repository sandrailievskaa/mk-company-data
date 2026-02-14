<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Контролна табла';

    protected static ?string $title = 'Контролна табла';

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
