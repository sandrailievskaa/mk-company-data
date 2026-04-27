<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Ai\Agents\OfferAgent;
use App\Ai\Agents\RecommendationAgent;
use App\Enums\OfferStatus;
use App\Filament\Resources\Offers\OfferResource;
use App\Models\Company;
use App\Models\OfferTarget;
use App\Services\AiUsage\AiUsageLogSource;
use App\Services\AiUsage\AiUsageRecorder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Arr;
use Throwable;

class CreateOffer extends CreateRecord
{
    protected static string $resource = OfferResource::class;

    protected static bool $canCreateAnother = false;

    protected function fillForm(): void
    {
        parent::fillForm();

        $companyId = (int) request()->query('company_id', 0);
        if ($companyId > 0 && Company::query()->whereKey($companyId)->exists()) {
            $state = $this->form->getState();
            $state['company_id'] = $companyId;
            $this->form->fill($state);
        }
    }

    public array $aiRecommendations = [];

    /**
     * company_id => bool
     */
    public array $aiRecommendationSelections = [];

    public ?string $aiRecommendationError = null;

    protected Width|string|null $maxContentWidth = Width::SevenExtraLarge;

    public function getTitle(): string
    {
        return 'Креирај понуда';
    }

    public function getSubheading(): ?string
    {
        return 'НОВА ПОНУДА · НАЦРТ';
    }

    public function getPageClasses(): array
    {
        return array_merge(parent::getPageClasses(), [
            'mk-offer-create',
        ]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Зачувај нацрт')
            ->extraAttributes(['class' => 'mk-offer-create-submit mk-offer-create-save']);
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Откажи')
            ->url(OfferResource::getUrl('index'))
            ->color('gray')
            ->outlined();
    }

    /**
     * @return array<\Filament\Actions\Action | \Filament\Actions\ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    public function generateCompanyRecommendations(): void
    {
        $this->aiRecommendationError = null;
        $this->aiRecommendations = [];
        $this->aiRecommendationSelections = [];

        try {
            $state = $this->form->getState();

            $title = trim((string) ($state['recommendation_title'] ?? ''));
            $description = trim((string) ($state['additional_information'] ?? ''));
            $sector = $state['recommendation_sector'] ?? null;

            if (empty($sector) && ! empty($state['company_id'])) {
                $selectedCompany = Company::find($state['company_id']);
                $sector = $selectedCompany?->sector?->value;
            }

            if ($title === '' && $description === '') {
                $this->aiRecommendationError = 'Внеси барем наслов или дополнителен опис за да се генерираат препораки.';

                return;
            }

            if (empty($sector)) {
                $this->aiRecommendationError = 'Избери сектор (или прво избери компанија со сектор).';

                return;
            }

            $candidates = Company::query()
                ->select(['id', 'name', 'sector', 'city', 'activity_index', 'email'])
                ->where('sector', $sector)
                ->orderByDesc('activity_index')
                ->limit(50)
                ->get()
                ->map(function (Company $c) {
                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'sector' => $c->sector?->value ?? $c->sector,
                        'sector_label' => $c->sector?->getLabel(),
                        'city' => $c->city,
                        'activity_index' => (float) ($c->activity_index ?? 0),
                        'has_email' => ! empty($c->email),
                    ];
                })
                ->values()
                ->all();

            if (count($candidates) === 0) {
                $this->aiRecommendationError = 'Нема компании за избраниот сектор.';

                return;
            }

            $prompt = $this->buildRecommendationPrompt(
                title: $title,
                description: $description,
                sector: (string) $sector,
                candidates: $candidates,
            );

            $agent = RecommendationAgent::make();
            $response = $agent->prompt($prompt);

            app(AiUsageRecorder::class)->record(
                $response,
                AiUsageLogSource::RECOMMENDATION,
                auth()->id()
            );

            $rawJson = (string) ($response['recommendations_json'] ?? '');
            $decoded = json_decode($rawJson, true);

            if (! is_array($decoded)) {
                $this->aiRecommendationError = 'AI врати невалиден JSON за препораките. Пробај повторно.';

                return;
            }

            $candidateById = collect($candidates)->keyBy('id');
            $candidateIds = $candidateById->keys()->map(fn ($id) => (int) $id)->all();
            $candidateIdSet = array_flip($candidateIds);

            $final = [];
            foreach ($decoded as $item) {
                $companyId = (int) Arr::get($item, 'company_id');
                $reason = trim((string) Arr::get($item, 'reason', ''));

                if ($companyId <= 0 || $reason === '') {
                    continue;
                }
                if (! isset($candidateIdSet[$companyId])) {
                    continue;
                }

                $c = $candidateById->get($companyId);
                $final[] = [
                    'company_id' => $companyId,
                    'name' => $c['name'] ?? ('#'.$companyId),
                    'sector' => $c['sector'] ?? null,
                    'sector_label' => $c['sector_label'] ?? null,
                    'city' => $c['city'] ?? null,
                    'activity_index' => $c['activity_index'] ?? null,
                    'has_email' => $c['has_email'] ?? false,
                    'reason' => $reason,
                ];

                if (count($final) >= 15) {
                    break;
                }
            }

            if (count($final) === 0) {
                $this->aiRecommendationError = 'AI не врати валидни препораки за кандидатите. Пробај повторно.';

                return;
            }

            $this->aiRecommendations = $final;
            foreach ($final as $rec) {
                $this->aiRecommendationSelections[(string) $rec['company_id']] = false;
            }
        } catch (Throwable) {
            $this->aiRecommendationError = 'Неуспешно генерирање на препораки. Провери API клуч и пробај повторно.';
        }
    }

    public function generateOfferContent(): void
    {
        try {
            $state = $this->form->getState();
            $companyId = $state['company_id'] ?? null;
            $additionalInformation = trim((string) ($state['additional_information'] ?? ''));

            if ($companyId === null && $additionalInformation === '') {
                Notification::make()
                    ->title('Пополни податоци')
                    ->body('Одбери компанија и/или дополнителен опис за AI.')
                    ->warning()
                    ->send();

                return;
            }

            $company = $companyId ? Company::find($companyId) : null;
            $companyContext = $company ? "Company: {$company->name}" : '';
            $prompt = trim("{$companyContext}\n\n{$additionalInformation}");

            if ($prompt === '') {
                Notification::make()
                    ->title('Нема влез')
                    ->body('Пополни барем едно поле за AI.')
                    ->warning()
                    ->send();

                return;
            }

            $agent = OfferAgent::make();
            $response = $agent->prompt($prompt);

            app(AiUsageRecorder::class)->record(
                $response,
                AiUsageLogSource::OFFER,
                auth()->id()
            );

            $this->form->fill(array_merge(
                $this->form->getState(),
                [
                    'title' => $response['title'],
                    'content' => $response['content'],
                ]
            ));

            Notification::make()
                ->title('Содржината е генерирана')
                ->body('Проверете го текстот и прилагодете го по потреба.')
                ->success()
                ->send();
        } catch (Throwable) {
            Notification::make()
                ->title('Генерирањето не успеа')
                ->body('Проверете ја врската / API поставките и пробајте повторно.')
                ->danger()
                ->send();
        }
    }

    protected function afterCreate(): void
    {
        $selectedIds = collect($this->aiRecommendationSelections)
            ->filter(fn ($selected) => (bool) $selected)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($selectedIds->isEmpty() || ! $this->record) {
            return;
        }

        $reasonByCompanyId = collect($this->aiRecommendations)->keyBy('company_id')->map(fn ($r) => $r['reason'] ?? null);

        foreach ($selectedIds as $companyId) {
            OfferTarget::updateOrCreate(
                [
                    'offer_id' => $this->record->id,
                    'company_id' => $companyId,
                ],
                [
                    'reason' => $reasonByCompanyId->get($companyId),
                ],
            );
        }
    }

    private function buildRecommendationPrompt(string $title, string $description, string $sector, array $candidates): string
    {
        $offerDetails = [
            'title' => $title,
            'description' => $description,
            'sector' => $sector,
        ];

        return trim(
            "Offer details:\n".
            json_encode($offerDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).
            "\n\n".
            "Candidate companies (top 50 by activity_index for the sector):\n".
            json_encode($candidates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = OfferStatus::Pending->value;

        return $data;
    }
}
