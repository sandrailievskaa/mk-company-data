<?php

namespace App\Services;

use App\Enums\EmailSource;
use App\Enums\ScrapeRunStatus;
use App\Models\Company;
use App\Models\ScrapeRun;
use App\Support\CompanyNameNormalizer;
use App\Support\RobotsTxt;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

class CompanyScraperService
{
    public function __construct(private readonly CompanyNameNormalizer $normalizer) {}

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

        $browser = $this->makeBrowser();
        $this->assertAllowedByRobots($browser, $sector);

        $delayMicros = max(0, (int) config('scraping.delay_ms')) * 1000;
        $url = rtrim((string) config('scraping.base_url'), '/').'/'.$sector;
        $pages = $seen = $created = $updated = $emails = 0;

        do {
            $crawler = $this->requestWithRetry($browser, $url);
            $pages++;

            foreach ($crawler->filter('.result') as $result) {
                $company = $this->parseResult($result);
                if (blank($company['name'])) {
                    continue;
                }
                $seen++;

                // Duplicate detection is done on the normalized name, so spelling,
                // case and legal-form variants map to a single record.
                $model = Company::query()->firstWhere('normalized_name', $this->normalizer->normalize($company['name']))
                    ?? new Company(['name' => $company['name']]);

                $model->fill([
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
                usleep($delayMicros);
            }
        } while ($url);

        $run->update([
            'pages_fetched' => $pages,
            'records_seen' => $seen,
            'records_created' => $created,
            'records_updated' => $updated,
            'emails_found' => $emails,
        ]);

        return $run;
    }

    private function makeBrowser(): HttpBrowser
    {
        return new HttpBrowser(HttpClient::create([
            // `timeout` is the max idle time while transferring; `max_duration` caps total request time.
            'timeout' => config('scraping.timeout'),
            'max_duration' => config('scraping.max_duration'),
            'headers' => [
                'User-Agent' => config('scraping.user_agent'),
                'Accept-Language' => 'mk-MK,mk;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
        ]));
    }

    private function assertAllowedByRobots(HttpBrowser $browser, string $sector): void
    {
        if (! config('scraping.respect_robots')) {
            return;
        }

        $base = rtrim((string) config('scraping.base_url'), '/');

        try {
            $browser->request('GET', $base.'/robots.txt');
            $robots = new RobotsTxt($browser->getResponse()->getContent(false));
        } catch (Throwable $e) {
            Log::warning('Could not read robots.txt, continuing: '.$e->getMessage());

            return;
        }

        if (! $robots->isAllowed('/'.$sector)) {
            throw new RuntimeException("robots.txt disallows scraping /{$sector}");
        }

        $crawlDelay = $robots->crawlDelaySeconds();
        if ($crawlDelay !== null && $crawlDelay * 1000 > (int) config('scraping.delay_ms')) {
            Log::warning(sprintf(
                'robots.txt asks for Crawl-delay %ss but SCRAPER_DELAY_MS is %dms.',
                $crawlDelay,
                (int) config('scraping.delay_ms')
            ));
        }
    }

    private function requestWithRetry(HttpBrowser $browser, string $url): Crawler
    {
        $attempts = max(1, (int) config('scraping.retries'));
        $delayMs = 500;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                return $browser->request('GET', $url);
            } catch (TransportException|TimeoutException $e) {
                if ($i === $attempts) {
                    throw $e;
                }

                // Exponential backoff (0.5s, 1s, 2s, 4s...)
                usleep($delayMs * 1000);
                $delayMs *= 2;
            }
        }

        // Unreachable, but keeps static analyzers happy.
        return $browser->request('GET', $url);
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

        return [
            'name' => $name,
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
