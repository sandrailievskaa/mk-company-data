<?php

namespace App\Services\EmailEnrichment\Strategies;

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\EmailExtractor;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Strategy 2: search-engine discovery using the company name and the .mk suffix.
 */
class SearchStrategy implements EmailStrategy
{
    public function __construct(
        private readonly ClientInterface $http,
        private readonly EmailExtractor $extractor,
    ) {}

    public function source(): EmailSource
    {
        return EmailSource::Search;
    }

    public function find(Company $company): ?string
    {
        if (! config('enrichment.search_enabled')) {
            return null;
        }

        try {
            $query = urlencode("\"{$company->name}\" email @mk");
            $response = $this->http->request('GET', "https://www.google.com/search?q={$query}", [
                'timeout' => (int) config('enrichment.timeouts.search'),
            ]);

            return $this->extractor->extract((string) $response->getBody());
        } catch (GuzzleException) {
            return null;
        }
    }
}
