<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Ai\Agents\OfferAgent;
use App\Filament\Resources\Offers\OfferResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;

class CreateOffer extends CreateRecord
{
    protected static string $resource = OfferResource::class;

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
