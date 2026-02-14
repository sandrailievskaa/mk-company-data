<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Filament\Resources\Offers\OfferResource;
use App\Models\Company;
use App\Models\SentOffer;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;

class SendOffer extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = OfferResource::class;

    protected string $view = 'filament.resources.offers.pages.send-offer';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->form->fill([
            'only_with_email' => true,
            'only_active' => false,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Назад')
                ->url(OfferResource::getUrl('index')),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Forms\Components\Select::make('sector')
                    ->label('Сектор')
                    ->options(function () {
                        $options = [];
                        foreach (\App\Enums\SectorEnum::cases() as $case) {
                            $options[$case->value] = $case->getLabel();
                        }

                        return $options;
                    })
                    ->searchable()
                    ->required()
                    ->helperText('Мора да избереш сектор за да испратиш понуди')
                    ->placeholder('Избери сектор'),
                Forms\Components\Select::make('city')
                    ->label('Град (опционално)')
                    ->options(function () {
                        return Company::query()
                            ->whereNotNull('city')
                            ->where('city', '!=', '')
                            ->distinct()
                            ->orderBy('city')
                            ->pluck('city', 'city')
                            ->toArray();
                    })
                    ->searchable()
                    ->placeholder('Сите градови'),
                Forms\Components\Toggle::make('only_with_email')
                    ->label('Само компании со email')
                    ->default(true),
                Forms\Components\Toggle::make('only_active')
                    ->label('Само активни компании (activity_index > 0)')
                    ->default(false),
            ]);
    }

    public function send(): void
    {
        $data = $this->form->getState();

        // Валидирај дека секторот е избран
        if (empty($data['sector'])) {
            Notification::make()
                ->title('Грешка')
                ->body('Мора да избереш сектор за да испратиш понуди.')
                ->danger()
                ->send();

            return;
        }

        $query = Company::query();

        // Филтрирај по сектор (задолжително)
        $query->where('sector', $data['sector']);

        // Филтрирај по град (опционално)
        if (! empty($data['city'])) {
            $query->where('city', $data['city']);
        }

        // Филтрирај по email (опционално)
        if ($data['only_with_email'] ?? false) {
            $query->whereNotNull('email');
        }

        // Филтрирај по активност (опционално)
        if ($data['only_active'] ?? false) {
            $query->where('activity_index', '>', 0);
        }

        $companies = $query->get();
        $totalCompanies = $companies->count();

        if ($totalCompanies === 0) {
            Notification::make()
                ->title('Нема компании')
                ->body('Не се пронајдени компании според избраните филтри.')
                ->warning()
                ->send();

            return;
        }

        $sentCount = 0;
        $skippedCount = 0;

        $record = $this->getRecord();

        foreach ($companies as $company) {
            // Провери дали веќе е испратена понудата на оваа компанија
            $existing = SentOffer::where('offer_id', $record->id)
                ->where('company_id', $company->id)
                ->first();

            if ($existing) {
                $skippedCount++;

                continue;
            }

            // Креирај SentOffer запис
            SentOffer::create([
                'offer_id' => $record->id,
                'company_id' => $company->id,
                'status' => 'pending',
            ]);

            $sentCount++;
        }

        $sectorLabel = \App\Enums\SectorEnum::from($data['sector'])->getLabel();

        Notification::make()
            ->title('Понудите се подготвени за испраќање')
            ->body("Сектор: {$sectorLabel}\n{$sentCount} понуди креирани. {$skippedCount} прескокнати (веќе испратени).")
            ->success()
            ->send();

        $this->redirect(OfferResource::getUrl('index'));
    }
}
