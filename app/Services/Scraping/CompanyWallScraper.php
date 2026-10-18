<?php

namespace App\Services\Scraping;

use App\Exceptions\SourceBlockedException;
use App\Models\Company;
use App\Support\CompanyNameNormalizer;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Looks a company up on CompanyWall (public company profile) and reads its
 * registry identifiers: tax number (ЕДБ), registration number (ЕМБС), founding
 * date, size class and number of employees. A profile is only accepted when its
 * name matches the company, so a wrong company is never attached.
 */
class CompanyWallScraper
{
    public function __construct(
        private readonly PoliteBrowser $http,
        private readonly CompanyNameNormalizer $normalizer,
    ) {}

    /**
     * Search, verify the match and store the registry data.
     * Returns true when a matching profile was found.
     */
    public function scrape(Company $company): bool
    {
        $profileUrl = $this->findProfileUrl($company);

        if ($profileUrl === null) {
            $company->forceFill(['companywall_scraped_at' => now()])->save();

            return false;
        }

        $this->http->pause();
        $url = $this->absoluteUrl($profileUrl);
        $crawler = $this->http->get($url);
        $this->assertNotBlocked($crawler);

        $company->forceFill($this->parse($crawler) + [
            'companywall_url' => $profileUrl,
            'companywall_scraped_at' => now(),
        ])->save();

        return true;
    }

    /**
     * Candidate profile path whose slug matches the company name (and city), or null.
     */
    public function findProfileUrl(Company $company): ?string
    {
        $search = $this->absoluteUrl('/prebaruvanje?n='.urlencode((string) $company->name));

        if (! $this->http->isAllowed($search)) {
            return null;
        }

        $crawler = $this->http->get($search);
        $this->assertNotBlocked($crawler);
        $this->http->pause();

        $paths = array_values(array_unique($crawler->filter('a[href^="/kompanija/"]')->each(
            fn (Crawler $a) => (string) $a->attr('href')
        )));

        foreach ($paths as $path) {
            if ($this->slugMatches($path, $company)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * "/kompanija/алгоритмика-дооел-скопје/MMJLDXD" matches "Алгоритмика" in "Скопје":
     * the slug must start with the normalized name; anything left must be the city.
     */
    public function slugMatches(string $path, Company $company): bool
    {
        $segments = explode('/', trim($path, '/'));
        $slug = urldecode($segments[1] ?? '');
        $slug = $this->normalizer->normalize(str_replace('-', ' ', $slug));
        $name = $this->normalizer->normalize($company->name);

        if ($name === '' || ! str_starts_with($slug.' ', $name.' ')) {
            return false;
        }

        $rest = trim(substr($slug, strlen($name)));
        $city = $this->normalizer->normalize((string) $company->city);

        // Legal forms are stripped by the normalizer, so what remains is the seat.
        return $rest === '' || ($city !== '' && ($rest === $city || str_ends_with($rest, $city)));
    }

    /**
     * @return array{edb: ?string, embs: ?string, founded_on: ?string, company_size: ?string, employees: ?int}
     */
    public function parse(Crawler $crawler): array
    {
        $text = preg_replace('/\s+/u', ' ', $crawler->filter('body')->text('', true)) ?? '';

        $founded = $this->match('/Датум на основање\s*(\d{1,2}\.\d{1,2}\.\d{4})/u', $text);

        return [
            'edb' => $this->match('/ЕДБ\s*(\d{10,13})/u', $text),
            'embs' => $this->match('/ЕМБС\s*(\d{6,8})/u', $text),
            'founded_on' => $founded ? Carbon::createFromFormat('j.n.Y', $founded)->toDateString() : null,
            'company_size' => $this->match('/класифицирана како (\p{L}+) бизнис/u', $text),
            'employees' => ($n = $this->match('/брои (\d+) работн/u', $text)) !== null ? (int) $n : null,
        ];
    }

    /**
     * After a number of anonymous requests CompanyWall redirects to its registration page.
     * That is a wall, not an empty result: stop instead of recording "no match".
     */
    private function assertNotBlocked(Crawler $crawler): void
    {
        $uri = (string) $crawler->getUri();

        if (str_contains($uri, '/registracija') || str_contains($uri, '/Account/Login') || str_contains($uri, '/login')) {
            throw new SourceBlockedException('CompanyWall requires sign-up for further lookups ('.$uri.')');
        }
    }

    private function match(string $pattern, string $text): ?string
    {
        return preg_match($pattern, $text, $m) === 1 ? $m[1] : null;
    }

    private function absoluteUrl(string $path): string
    {
        return rtrim((string) config('scraping.companywall_url'), '/').'/'.ltrim($path, '/');
    }
}
