<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'Компании';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Нова компанија'),
        ];
    }
}
