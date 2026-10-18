<?php

namespace App\Services\EmailEnrichment;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Reads a company website (home page, then common contact pages) and returns the
 * most plausible contact address found there.
 */
class SiteEmailFinder
{
    public function __construct(
        private readonly ClientInterface $http,
        private readonly EmailExtractor $extractor,
    ) {}

    /**
     * @param  list<string>  $baseUrls  alternatives for the same site (https/http, with/without www)
     */
    public function find(array $baseUrls): ?string
    {
        $timeout = (int) config('enrichment.timeouts.website');

        foreach ($baseUrls as $base) {
            $html = $this->fetch($base, $timeout);
            if ($html === null) {
                continue;
            }

            $email = $this->extractor->extract($html) ?? $this->fromContactPages($base, $timeout);
            if ($email !== null) {
                return $email;
            }
        }

        return null;
    }

    /**
     * Whether the URL answers with a page (used to confirm a discovered website).
     */
    public function isReachable(string $url): bool
    {
        return $this->fetch($url, (int) config('enrichment.timeouts.website')) !== null;
    }

    /**
     * URL variants to try for a stored website address.
     *
     * @return list<string>
     */
    public static function variants(string $website): array
    {
        $host = parse_url(str_contains($website, '://') ? $website : 'http://'.$website, PHP_URL_HOST);
        $path = rtrim((string) parse_url(str_contains($website, '://') ? $website : 'http://'.$website, PHP_URL_PATH), '/');

        if (! is_string($host) || $host === '') {
            return [];
        }

        $host = preg_replace('/^www\./i', '', $host);

        return [
            "https://{$host}{$path}", "http://{$host}{$path}",
            "https://www.{$host}{$path}", "http://www.{$host}{$path}",
        ];
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
