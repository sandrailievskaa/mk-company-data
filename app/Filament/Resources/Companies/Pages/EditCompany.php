<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Enums\EmailSource;
use App\Filament\Resources\Companies\CompanyResource;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    /**
     * A hand-edited e-mail is recorded as manual; clearing it clears its provenance.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newEmail = $data['email'] ?? null;

        if ($newEmail !== $this->record->email) {
            $data['email_source'] = filled($newEmail) ? EmailSource::Manual : null;
            $data['email_found_at'] = filled($newEmail) ? now() : null;
        }

        return $data;
    }

    public function getTitle(): string
    {
        return 'Уреди компанија';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
