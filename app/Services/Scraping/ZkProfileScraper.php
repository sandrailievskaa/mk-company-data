<?php

namespace App\Services\Scraping;

use App\Models\Company;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Reads a company's public profile page on zk.mk: official website, social
 * profiles and the activities it is listed under.
 */
class ZkProfileScraper
{
    public function __construct(private readonly PoliteBrowser $http) {}

    /**
     * @return array{website: ?string, facebook_url: ?string, instagram_url: ?string, activities: ?string}|null null when the page is unavailable
     */
    public function fetch(string $profileUrl): ?array
    {
        $url = $this->absoluteUrl($profileUrl);

        if (! $this->http->isAllowed($url)) {
            return null;
        }

        $crawler = $this->http->get($url);

        if ($this->http->statusCode() >= 400 || $crawler->filter('.result.indetails, .result .companyname')->count() === 0) {
            return null;
        }

        return $this->parse($crawler);
    }

    /**
     * Fetch and store the profile data. Returns false if the profile could not be read.
     */
    public function scrape(Company $company): bool
    {
        $data = $company->profile_url ? $this->fetch($company->profile_url) : null;

        $company->forceFill($data !== null
            ? $data + ['profile_scraped_at' => now()]
            : ['profile_scraped_at' => now()])->save();

        return $data !== null;
    }

    /**
     * @return array{website: ?string, facebook_url: ?string, instagram_url: ?string, activities: ?string}
     */
    public function parse(Crawler $crawler): array
    {
        return [
            'website' => $this->externalLink($crawler, 'a.website'),
            'facebook_url' => $this->externalLink($crawler, 'a.facebook'),
            'instagram_url' => $this->externalLink($crawler, 'a.instagram'),
            'activities' => $this->activities($crawler),
        ];
    }

    private function externalLink(Crawler $crawler, string $selector): ?string
    {
        $node = $crawler->filter($selector);
        if ($node->count() === 0) {
            return null;
        }

        $href = trim((string) $node->first()->attr('href'));
        $host = parse_url($href, PHP_URL_HOST);

        // Missing links are rendered as an empty "https://" placeholder.
        if (! is_string($host) || $host === '' || str_ends_with($host, 'zk.mk')) {
            return null;
        }

        return mb_substr($href, 0, 255);
    }

    private function activities(Crawler $crawler): ?string
    {
        $links = $crawler->filter('.shortdescription a')->each(fn (Crawler $a) => trim($a->text()));
        $links = array_values(array_unique(array_filter($links)));

        return $links === [] ? null : implode(', ', $links);
    }

    private function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return rtrim((string) config('scraping.base_url'), '/').'/'.ltrim($path, '/');
    }
}
