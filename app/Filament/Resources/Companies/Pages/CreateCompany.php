<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Enums\EmailSource;
use App\Filament\Resources\Companies\CompanyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (filled($data['email'] ?? null)) {
            $data['email_source'] = EmailSource::Manual;
            $data['email_found_at'] = now();
        }

        return $data;
    }

    public function getTitle(): string
    {
        return 'Нова компанија';
    }
}
