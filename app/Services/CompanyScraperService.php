<?php

namespace App\Services;

use App\Enums\EmailSource;
use App\Enums\ScrapeRunStatus;
use App\Models\Company;
use App\Models\ScrapeRun;
use App\Services\Scraping\PoliteBrowser;
use App\Support\CompanyNameNormalizer;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class CompanyScraperService
{
    public function __construct(
        private readonly CompanyNameNormalizer $normalizer,
        private readonly PoliteBrowser $http,
    ) {}

    /**
     * Scrape one sector, paginating to the end, and record metrics on the run.
     */
    public function scrapeCompanies(string $sector, ?ScrapeRun $run = null): ScrapeRun
    {
        $run ??= ScrapeRun::create([
            'sector' => $sector,
            'status' => ScrapeRunStatus::Running,
            'started_at' => now(),
        ]);

        $url = rtrim((string) config('scraping.base_url'), '/').'/'.$sector;
        if (! $this->http->isAllowed($url)) {
            throw new RuntimeException("robots.txt disallows scraping /{$sector}");
        }

        $pages = $seen = $created = $updated = $emails = $duplicates = 0;
        $seenInRun = [];

        do {
            $crawler = $this->http->get($url);
            $pages++;

            foreach ($crawler->filter('.result') as $result) {
                $company = $this->parseResult($result);
                if (blank($company['name'])) {
                    continue;
                }
                $seen++;

                // Duplicate detection is done on the normalized name, so spelling,
                // case and legal-form variants map to a single record.
                $normalized = $this->normalizer->normalize($company['name']);
                if (isset($seenInRun[$normalized])) {
                    $duplicates++;
                }
                $seenInRun[$normalized] = true;

                $model = Company::query()->firstWhere('normalized_name', $normalized)
                    ?? new Company(['name' => $company['name']]);

                $model->fill([
                    'profile_url' => $company['profile_url'] ?? $model->profile_url,
                    'city' => $company['city'],
                    'address' => $company['address'],
                    'phone' => $company['phone'],
                    'sector' => $sector,
                ])->save();

                // Count how often we see this company across scraping runs.
                // This is used later to compute the normalized frequency score (F).
                if ($model->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                    $model->increment('scrape_count');
                }

                // If scraping found an email, only set it when we don't already have one.
                // This avoids overwriting enriched / manually set emails with null.
                if (! empty($company['email']) && empty($model->email)) {
                    $model->update([
                        'email' => $company['email'],
                        'email_source' => EmailSource::Registry,
                        'email_found_at' => now(),
                    ]);
                    $emails++;
                }
            }

            $next = $crawler->filter('.pagination a[rel="next"]');
            $url = $next->count() ? $next->attr('href') : null;

            // Be polite and reduce the chance of rate limiting / connection resets.
            if ($url) {
                $this->http->pause();
            }
        } while ($url);

        $run->update([
            'pages_fetched' => $pages,
            'records_seen' => $seen,
            'records_created' => $created,
            'records_updated' => $updated,
            'duplicates_skipped' => $duplicates,
            'emails_found' => $emails,
        ]);

        return $run;
    }

    private function getText(Crawler $crawler, string $selector): ?string
    {
        return $crawler->filter($selector)->count()
            ? trim($crawler->filter($selector)->text())
            : null;
    }

    private function getAttr(Crawler $crawler, string $selector, string $attr): ?string
    {
        return $crawler->filter($selector)->count()
            ? $crawler->filter($selector)->attr($attr)
            : null;
    }

    private function parseResult($result): array
    {
        $result_crawler = new Crawler($result);

        $name = $this->getText(
            $result_crawler,
            '.companyname span[itemprop="name"]'
        );

        $city = $this->getText(
            $result_crawler,
            'ul.details li[origcaption="Место"]'
        );
        $city = $city ? str_replace('Место:', '', $city) : null;

        $address = $this->getText(
            $result_crawler,
            'ul.details li[origcaption="Адреса"]'
        );
        $address = $address ? str_replace('Адреса:', '', $address) : null;

        $phone = $this->getAttr(
            $result_crawler,
            '.tcall',
            'href'
        );
        $phone = $phone ? str_replace('tel:', '', $phone) : null;

        // Try to extract email from the result HTML
        $email = $this->extractEmailFromHtml($result_crawler->html());

        $profileUrl = $this->getAttr($result_crawler, 'a.companyname', 'href');

        return [
            'name' => $name,
            'profile_url' => $profileUrl ? trim($profileUrl) : null,
            'city' => $city,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
        ];
    }

    private function extractEmailFromHtml(string $html): ?string
    {
        // Email regex pattern
        $pattern = '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/';

        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[0] as $email) {
                $email = strtolower(trim($email));

                // Skip common non-company emails and invalid patterns
                if (
                    str_contains($email, 'example.com') ||
                    str_contains($email, 'test.com') ||
                    str_contains($email, 'noreply') ||
                    str_contains($email, 'no-reply') ||
                    str_contains($email, 'donotreply') ||
                    str_contains($email, 'cdn77.org') ||
                    str_contains($email, 'zk.mk')
                ) {
                    continue;
                }

                // Prefer .mk domains for Macedonian companies
                if (str_ends_with($email, '.mk')) {
                    return $email;
                }
            }

            // Return first valid email if no .mk domain found
            $email = strtolower(trim($matches[0][0]));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return null;
    }
}
