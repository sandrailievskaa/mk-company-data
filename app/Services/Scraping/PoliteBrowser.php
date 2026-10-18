<?php

namespace App\Services\Scraping;

use App\Support\RobotsTxt;
use Illuminate\Support\Facades\Log;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;

/**
 * HTTP client shared by all scrapers: configured timeouts and user agent, retries
 * with exponential backoff, a fixed pause between requests and robots.txt checks.
 */
class PoliteBrowser
{
    private ?HttpBrowser $browser = null;

    /** @var array<string, RobotsTxt> */
    private array $robots = [];

    public function get(string $url): Crawler
    {
        $attempts = max(1, (int) config('scraping.retries'));
        $delayMs = 500;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                return $this->browser()->request('GET', $url);
            } catch (TransportException|TimeoutException $e) {
                if ($i === $attempts) {
                    throw $e;
                }

                // Exponential backoff (0.5s, 1s, 2s, 4s...)
                usleep($delayMs * 1000);
                $delayMs *= 2;
            }
        }

        return $this->browser()->request('GET', $url);
    }

    public function statusCode(): int
    {
        return $this->browser()->getInternalResponse()->getStatusCode();
    }

    /**
     * Sleep between two requests to the same host.
     */
    public function pause(): void
    {
        usleep(max(0, (int) config('scraping.delay_ms')) * 1000);
    }

    /**
     * Whether robots.txt of the URL's host allows the path for all agents.
     * Fails open (allowed) if robots.txt cannot be read.
     */
    public function isAllowed(string $url): bool
    {
        if (! config('scraping.respect_robots')) {
            return true;
        }

        $parts = parse_url($url);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        if (! isset($this->robots[$origin])) {
            try {
                $this->browser()->request('GET', $origin.'/robots.txt');
                $this->robots[$origin] = new RobotsTxt($this->browser()->getResponse()->getContent(false));
            } catch (\Throwable $e) {
                Log::warning("Could not read {$origin}/robots.txt, continuing: ".$e->getMessage());
                $this->robots[$origin] = new RobotsTxt('');
            }
        }

        $robots = $this->robots[$origin];

        $crawlDelay = $robots->crawlDelaySeconds();
        if ($crawlDelay !== null && $crawlDelay * 1000 > (int) config('scraping.delay_ms')) {
            Log::warning(sprintf(
                '%s robots.txt asks for Crawl-delay %ss but SCRAPER_DELAY_MS is %dms.',
                $origin, $crawlDelay, (int) config('scraping.delay_ms')
            ));
        }

        return $robots->isAllowed(($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : ''));
    }

    private function browser(): HttpBrowser
    {
        return $this->browser ??= new HttpBrowser(HttpClient::create([
            // `timeout` is the max idle time while transferring; `max_duration` caps total request time.
            'timeout' => config('scraping.timeout'),
            'max_duration' => config('scraping.max_duration'),
            'headers' => [
                'User-Agent' => config('scraping.user_agent'),
                'Accept-Language' => 'mk-MK,mk;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
        ]));
    }
}
