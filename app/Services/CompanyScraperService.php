<?php

namespace App\Services;

use App\Models\Company;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;

class CompanyScraperService
{
    public function scrapeCompanies(string $sector): void
    {
        $browser = new HttpBrowser(HttpClient::create([
            // `timeout` is the max idle time while transferring; `max_duration` caps total request time.
            'timeout' => 60,
            'max_duration' => 90,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept-Language' => 'mk-MK,mk;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
        ]));

        $url = 'https://zk.mk/'.$sector;

        do {
            $crawler = $this->requestWithRetry($browser, $url);
            $results = $crawler->filter('.result');

            foreach ($results as $result) {
                $company = $this->parseResult($result);
                $model = Company::query()->updateOrCreate([
                    'name' => $company['name'],
                ], [
                    'city' => $company['city'],
                    'address' => $company['address'],
                    'phone' => $company['phone'],
                    'sector' => $sector,
                ]);

                // Count how often we see this company across scraping runs.
                // This is used later to compute the normalized frequency score (F).
                if (! $model->wasRecentlyCreated) {
                    $model->increment('scrape_count');
                }

                // If scraping found an email, only set it when we don't already have one.
                // This avoids overwriting enriched / manually set emails with null.
                if (! empty($company['email']) && empty($model->email)) {
                    $model->update(['email' => $company['email']]);
                }
            }

            $url = $crawler->filter('.pagination a[rel="next"]')->count()
                ? $crawler->filter('.pagination a[rel="next"]')->attr('href')
                : null;

            // Be polite and reduce the chance of rate limiting / connection resets.
            if ($url) {
                usleep(250000); // 0.25s
            }
        } while ($url);
    }

    private function requestWithRetry(HttpBrowser $browser, string $url): Crawler
    {
        $attempts = 5;
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
