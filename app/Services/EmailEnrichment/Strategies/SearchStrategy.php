<?php

namespace App\Services\EmailEnrichment\Strategies;

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\SiteEmailFinder;
use App\Support\CompanyNameNormalizer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Strategy 2: search-engine discovery for companies without a known website.
 * The company name and city are searched; a result is accepted only when its
 * domain visibly belongs to the company, and its site is then read for an address.
 * The discovered website is remembered on the company.
 */
class SearchStrategy implements EmailStrategy
{
    private const DIRECTORIES = [
        'zk.mk', 'companywall', 'facebook.', 'instagram.', 'linkedin.', 'youtube.', 'wikipedia.', 'twitter.', 'x.com',
        '1188.mk', 'crm.com.mk', 'yellowpages', 'google.', 'bing.', 'duckduckgo.', 'tripadvisor.', 'booking.com',
        'olx.', 'pazar3.mk', 'kupindo.mk', 'time.mk', 'mapcarta', 'foursquare', 'yelp.',
    ];

    /** Website found by the last successful find(), if any. */
    public ?string $discoveredWebsite = null;

    public function __construct(
        private readonly ClientInterface $http,
        private readonly SiteEmailFinder $finder,
        private readonly CompanyNameNormalizer $normalizer,
    ) {}

    public function source(): EmailSource
    {
        return EmailSource::Search;
    }

    public function find(Company $company): ?string
    {
        $this->discoveredWebsite = null;

        if (! config('enrichment.search_enabled') || filled($company->website)) {
            return null;
        }

        foreach ($this->searchResults($company) as $url) {
            if (! $this->domainBelongsTo($url, $company)) {
                continue;
            }

            $email = $this->finder->find(SiteEmailFinder::variants($url));
            if ($email !== null) {
                $this->discoveredWebsite = $this->origin($url);

                return $email;
            }
        }

        return null;
    }

    /**
     * Whether the host of the URL carries (a prefix of) the company's name.
     */
    public function domainBelongsTo(string $url, Company $company): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach (self::DIRECTORIES as $directory) {
            if ($host === '' || str_contains($host, $directory)) {
                return false;
            }
        }

        $label = explode('.', preg_replace('/^www\./', '', $host))[0];
        $name = $this->normalizer->compact($company->name);

        if (strlen($name) < 4 || strlen($label) < 4) {
            return false;
        }

        $needle = substr($name, 0, min(strlen($name), 8));

        return str_contains($label, $needle) || str_contains($name, substr($label, 0, min(strlen($label), 8)));
    }

    /**
     * @return list<string>
     */
    private function searchResults(Company $company): array
    {
        try {
            $query = urlencode(trim($company->name.' '.($company->city ?? '')));
            $response = $this->http->request('GET', "https://html.duckduckgo.com/html/?q={$query}", [
                'timeout' => (int) config('enrichment.timeouts.search'),
            ]);
            $html = (string) $response->getBody();
        } catch (GuzzleException) {
            return [];
        }

        preg_match_all('/uddg=([^"&]+)/', $html, $m);

        return array_slice(array_values(array_unique(array_map('urldecode', $m[1] ?? []))), 0, 6);
    }

    private function origin(string $url): string
    {
        $p = parse_url($url);

        return ($p['scheme'] ?? 'http').'://'.($p['host'] ?? '');
    }
}
