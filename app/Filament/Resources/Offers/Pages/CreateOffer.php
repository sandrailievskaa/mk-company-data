<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Ai\Agents\OfferAgent;
use App\Ai\Agents\RecommendationAgent;
use App\Filament\Resources\Offers\OfferResource;
use App\Models\Company;
use App\Models\OfferTarget;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Throwable;

class CreateOffer extends CreateRecord
{
    protected static string $resource = OfferResource::class;

    public array $aiRecommendations = [];

    /**
     * company_id => bool
     */
    public array $aiRecommendationSelections = [];

    public ?string $aiRecommendationError = null;

    public function getTitle(): string
    {
        return 'Нова понуда';
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
                $this->aiRecommendationError = 'Внеси барем наслов или опис за да се генерираат препораки.';
                return;
            }

            if (empty($sector)) {
                $this->aiRecommendationError = 'Избери сектор за препораки (или избери компанија со сектор).';
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
                    'name' => $c['name'] ?? ('#' . $companyId),
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
        } catch (Throwable $e) {
            $this->aiRecommendationError = 'Неуспешно генерирање препораки. Провери API клуч и пробај повторно.';
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $additionalInformation = $data['additional_information'] ?? '';
        $companyId = $data['company_id'] ?? null;

        // Get company information for context
        $company = $companyId ? Company::find($companyId) : null;
        $companyContext = $company ? "Company: {$company->name}" : '';

        // Build the prompt with additional information
        $prompt = trim("{$companyContext}\n\n{$additionalInformation}");

        // Generate offer using AI agent
        $agent = OfferAgent::make();
        $response = $agent->prompt($prompt);

        // Get structured output from the response (array access works for StructuredAgentResponse)
        // Replace form data with AI-generated content
        return [
            'company_id' => $data['company_id'],
            'title' => $response['title'],
            'content' => $response['content'],
        ];
    }
}
