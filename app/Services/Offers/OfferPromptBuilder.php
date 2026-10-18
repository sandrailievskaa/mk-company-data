<?php

namespace App\Services\Offers;

use App\Models\Company;

/**
 * Builds the structured company context sent to the offer-writing agent, so the
 * generated text is tailored to the sector, location and activity level of the
 * recipient instead of only its name.
 */
class OfferPromptBuilder
{
    public function build(?Company $company, string $additionalInformation = ''): string
    {
        $lines = [];

        if ($company) {
            $lines[] = 'Company profile:';
            $lines[] = '- Name: '.$company->name;
            $lines[] = '- Sector: '.($company->sector?->getLabel() ?? 'unknown');

            if (filled($company->city)) {
                $lines[] = '- City: '.trim((string) $company->city);
            }

            $lines[] = sprintf(
                '- Activity level: %s (index %.2f of 1.00)',
                $this->activityLabel((float) $company->activity_index),
                (float) $company->activity_index
            );
        }

        $additionalInformation = trim($additionalInformation);
        if ($additionalInformation !== '') {
            $lines[] = '';
            $lines[] = 'What we are offering / additional context:';
            $lines[] = $additionalInformation;
        }

        return implode("\n", $lines);
    }

    private function activityLabel(float $index): string
    {
        return match (true) {
            $index >= 0.6 => 'high',
            $index >= 0.3 => 'medium',
            default => 'low',
        };
    }
}
