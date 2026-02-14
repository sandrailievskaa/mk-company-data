<?php

namespace App\Services\EmailEnrichment;

use App\Models\Company;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class EmailEnrichmentService
{
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 15,
            'verify' => false,
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'mk-MK,mk;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
        ]);
    }

    /**
     * Try to find email for a company using multiple aggressive strategies
     */
    public function findEmail(Company $company): ?string
    {
        // Strategy 1: Try to find and scrape from company website
        $email = $this->getEmailFromCompanyWebsite($company);
        if ($email && $this->isValidEmail($email)) {
            return $email;
        }

        // Strategy 2: Try Google search for company email
        $email = $this->getEmailFromGoogleSearch($company);
        if ($email && $this->isValidEmail($email)) {
            return $email;
        }

        // Strategy 3: Try pattern-based email generation with multiple domains
        $email = $this->generateEmailPatterns($company);
        if ($email && $this->isValidEmail($email)) {
            return $email;
        }

        return null;
    }

    /**
     * Aggressively search for company website and extract emails
     */
    private function getEmailFromCompanyWebsite(Company $company): ?string
    {
        // Try multiple ways to find website
        $possibleDomains = $this->generatePossibleDomains($company);

        foreach ($possibleDomains as $domain) {
            // Try common website URLs
            $urls = [
                "https://{$domain}",
                "http://{$domain}",
                "https://www.{$domain}",
                "http://www.{$domain}",
            ];

            foreach ($urls as $url) {
                try {
                    $response = $this->httpClient->get($url, ['timeout' => 5]);
                    if ($response->getStatusCode() === 200) {
                        // Found website, now extract emails
                        $html = $response->getBody()->getContents();
                        $email = $this->extractEmailsFromText($html);
                        if ($email) {
                            return $email;
                        }

                        // Try contact page
                        $contactEmail = $this->tryContactPage($url);
                        if ($contactEmail) {
                            return $contactEmail;
                        }
                    }
                } catch (GuzzleException $e) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Try to find email from contact/about pages
     */
    private function tryContactPage(string $baseUrl): ?string
    {
        $contactPages = [
            '/contact',
            '/kontakt',
            '/contact-us',
            '/kontaktirajte-ne',
            '/about',
            '/za-nas',
            '/info',
        ];

        foreach ($contactPages as $page) {
            try {
                $url = rtrim($baseUrl, '/').$page;
                $response = $this->httpClient->get($url, ['timeout' => 5]);
                if ($response->getStatusCode() === 200) {
                    $html = $response->getBody()->getContents();
                    $email = $this->extractEmailsFromText($html);
                    if ($email) {
                        return $email;
                    }
                }
            } catch (GuzzleException $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Try Google search for company email
     */
    private function getEmailFromGoogleSearch(Company $company): ?string
    {
        try {
            // Search for company name + email
            $searchQuery = urlencode("\"{$company->name}\" email @mk");
            $searchUrl = "https://www.google.com/search?q={$searchQuery}";

            $response = $this->httpClient->get($searchUrl, [
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ],
            ]);

            $html = $response->getBody()->getContents();

            return $this->extractEmailsFromText($html);
        } catch (GuzzleException $e) {
            return null;
        }
    }

    /**
     * Generate possible domains for company
     */
    private function generatePossibleDomains(Company $company): array
    {
        $normalizedName = $this->normalizeCompanyName($company->name);
        $domains = [];

        // Try .mk first
        $domains[] = "{$normalizedName}.mk";

        // Try .com.mk
        $domains[] = "{$normalizedName}.com.mk";

        // Try .com
        $domains[] = "{$normalizedName}.com";

        // If name is long, try shortened version
        if (strlen($normalizedName) > 15) {
            $shortName = substr($normalizedName, 0, 12);
            $domains[] = "{$shortName}.mk";
            $domains[] = "{$shortName}.com.mk";
        }

        // Try without common words
        $nameWithoutCommon = preg_replace('/\b(doo|ad|llc|mk|mkd)\b/i', '', $normalizedName);
        $nameWithoutCommon = preg_replace('/\s+/', '', $nameWithoutCommon);
        if ($nameWithoutCommon && $nameWithoutCommon !== $normalizedName) {
            $domains[] = "{$nameWithoutCommon}.mk";
            $domains[] = "{$nameWithoutCommon}.com.mk";
        }

        return array_unique($domains);
    }

    /**
     * Generate email patterns with multiple domains
     */
    private function generateEmailPatterns(Company $company): ?string
    {
        $normalizedName = $this->normalizeCompanyName($company->name);
        $domains = $this->generatePossibleDomains($company);

        if (empty($domains)) {
            return null;
        }

        // Extended list of email patterns (most common first)
        $patterns = [
            'info',
            'contact',
            'kontakt',
            'office',
            'kancelarija',
            'sales',
            'prodazba',
            'hello',
            'marketing',
            'general',
            'admin',
            'support',
            'podrska',
        ];

        // For .mk domains, return most common pattern immediately
        // (info@companyname.mk is very common in Macedonia)
        foreach ($domains as $domain) {
            if (str_ends_with($domain, '.mk') && ! str_contains($domain, '.com.mk')) {
                return "info@{$domain}";
            }
        }

        // If no .mk domain, try .com.mk
        foreach ($domains as $domain) {
            if (str_ends_with($domain, '.com.mk')) {
                return "info@{$domain}";
            }
        }

        // Fallback to first domain with info pattern
        return "info@{$domains[0]}";
    }

    /**
     * Normalize company name for domain generation
     */
    private function normalizeCompanyName(string $name): string
    {
        // Remove common suffixes
        $name = preg_replace('/\s+(DOO|AD|A\.D\.|LLC|Ltd|Inc|Corp|Corporation|МКД|МК)\s*$/i', '', $name);

        // Remove special characters but keep spaces for now
        $name = preg_replace('/[^a-zA-Zа-яА-Я0-9\s]/u', '', $name);

        // Convert Macedonian Cyrillic to Latin
        $replacements = [
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Ђ' => 'Dj', 'Е' => 'E', 'Ж' => 'Z', 'З' => 'Z', 'И' => 'I',
            'Ј' => 'J', 'К' => 'K', 'Л' => 'L', 'Љ' => 'Lj', 'М' => 'M',
            'Н' => 'N', 'Њ' => 'Nj', 'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'Ћ' => 'C', 'У' => 'U', 'Ф' => 'F',
            'Х' => 'H', 'Ц' => 'C', 'Ч' => 'C', 'Џ' => 'Dz', 'Ш' => 'S',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'ђ' => 'dj', 'е' => 'e', 'ж' => 'z', 'з' => 'z', 'и' => 'i',
            'ј' => 'j', 'к' => 'k', 'л' => 'l', 'љ' => 'lj', 'м' => 'm',
            'н' => 'n', 'њ' => 'nj', 'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'ћ' => 'c', 'у' => 'u', 'ф' => 'f',
            'х' => 'h', 'ц' => 'c', 'ч' => 'c', 'џ' => 'dz', 'ш' => 's',
            'ѕ' => 'dz', 'ѓ' => 'gj', 'ќ' => 'kj',
        ];

        $name = strtr($name, $replacements);

        // Convert to lowercase
        $name = strtolower($name);

        // Remove spaces and special characters
        $name = preg_replace('/[^a-z0-9]/', '', $name);

        return $name;
    }

    /**
     * Extract emails from HTML/text
     */
    private function extractEmailsFromText(string $text): ?string
    {
        $pattern = '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/';

        if (preg_match_all($pattern, $text, $matches)) {
            $foundEmails = [];

            foreach ($matches[0] as $email) {
                $email = strtolower(trim($email));

                // Skip invalid emails
                if (
                    str_contains($email, 'example.com') ||
                    str_contains($email, 'test.com') ||
                    str_contains($email, 'noreply') ||
                    str_contains($email, 'no-reply') ||
                    str_contains($email, 'donotreply') ||
                    str_contains($email, 'cdn77.org') ||
                    str_contains($email, 'zk.mk') ||
                    str_contains($email, 'facebook.com') ||
                    str_contains($email, 'twitter.com') ||
                    str_contains($email, 'linkedin.com') ||
                    ! filter_var($email, FILTER_VALIDATE_EMAIL)
                ) {
                    continue;
                }

                $foundEmails[] = $email;
            }

            if (empty($foundEmails)) {
                return null;
            }

            // Prefer .mk domains
            foreach ($foundEmails as $email) {
                if (str_ends_with($email, '.mk') && ! str_contains($email, '.com.mk')) {
                    return $email;
                }
            }

            // Return first valid email
            return $foundEmails[0];
        }

        return null;
    }

    /**
     * Validate email format
     */
    private function isValidEmail(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Enrich a single company with email
     */
    public function enrichCompany(Company $company): bool
    {
        if ($company->email) {
            return true;
        }

        $email = $this->findEmail($company);

        if ($email) {
            $company->update(['email' => $email]);

            return true;
        }

        return false;
    }

    /**
     * Enrich multiple companies
     */
    public function enrichCompanies(int $limit = 100, bool $skipExisting = true, ?callable $callback = null): array
    {
        $query = Company::query();

        if ($skipExisting) {
            $query->whereNull('email');
        }

        $companies = $query->limit($limit)->get();
        $results = [
            'total' => $companies->count(),
            'enriched' => 0,
            'failed' => 0,
        ];

        foreach ($companies as $company) {
            try {
                $email = null;
                $success = false;
                $hadEmailBefore = ! empty($company->email);

                if ($hadEmailBefore) {
                    $success = true;
                    $email = $company->email;
                } else {
                    $email = $this->findEmail($company);
                    if ($email) {
                        $company->update(['email' => $email]);
                        $success = true;
                        $results['enriched']++;
                    } else {
                        $results['failed']++;
                    }
                }

                if ($callback) {
                    $callback($company, $success, $email, $hadEmailBefore);
                }

                // Small delay to avoid rate limiting
                usleep(300000); // 0.3 seconds
            } catch (\Exception $e) {
                $results['failed']++;
                if ($callback) {
                    $callback($company, false, null, false);
                }
            }
        }

        return $results;
    }
}
