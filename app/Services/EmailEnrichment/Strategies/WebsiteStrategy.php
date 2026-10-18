<?php

namespace App\Services\EmailEnrichment\Strategies;

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\DomainGuesser;
use App\Services\EmailEnrichment\EmailExtractor;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Strategy 1: guess the company website, then read the home page and contact pages.
 */
class WebsiteStrategy implements EmailStrategy
{
    public function __construct(
        private readonly ClientInterface $http,
        private readonly DomainGuesser $domains,
        private readonly EmailExtractor $extractor,
    ) {}

    public function source(): EmailSource
    {
        return EmailSource::Website;
    }

    public function find(Company $company): ?string
    {
        $timeout = (int) config('enrichment.timeouts.website');

        foreach ($this->domains->domainsFor($company) as $domain) {
            foreach (["https://{$domain}", "http://{$domain}", "https://www.{$domain}", "http://www.{$domain}"] as $base) {
                $html = $this->fetch($base, $timeout);
                if ($html === null) {
                    continue;
                }

                $email = $this->extractor->extract($html) ?? $this->fromContactPages($base, $timeout);
                if ($email !== null) {
                    return $email;
                }
            }
        }

        return null;
    }

    private function fromContactPages(string $base, int $timeout): ?string
    {
        foreach ((array) config('enrichment.contact_paths') as $path) {
            $html = $this->fetch(rtrim($base, '/').$path, $timeout);
            $email = $html === null ? null : $this->extractor->extract($html);
            if ($email !== null) {
                return $email;
            }
        }

        return null;
    }

    private function fetch(string $url, int $timeout): ?string
    {
        try {
            $response = $this->http->request('GET', $url, ['timeout' => $timeout]);

            return $response->getStatusCode() === 200 ? (string) $response->getBody() : null;
        } catch (GuzzleException) {
            return null;
        }
    }
}
