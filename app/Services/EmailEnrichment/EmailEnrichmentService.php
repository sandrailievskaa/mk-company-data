<?php

namespace App\Services\EmailEnrichment;

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\Strategies\EmailStrategy;
use App\Services\EmailEnrichment\Strategies\PatternStrategy;
use App\Services\EmailEnrichment\Strategies\SearchStrategy;
use App\Services\EmailEnrichment\Strategies\WebsiteStrategy;
use Throwable;

/**
 * Finds a contact e-mail for a company by trying the strategies in order of
 * reliability: the company website, a search-engine lookup and (only when enabled)
 * pattern-based generation. Every stored address records which strategy produced it
 * and whether its domain can receive mail.
 */
class EmailEnrichmentService
{
    /** Source of the e-mail returned by the most recent findEmail() call. */
    private ?EmailSource $lastSource = null;

    /** Website discovered by the last successful search, if any. */
    private ?string $lastWebsite = null;

    public function __construct(
        private readonly WebsiteStrategy $website,
        private readonly SearchStrategy $search,
        private readonly PatternStrategy $pattern,
        private readonly EmailExtractor $extractor,
        private readonly EmailDomainChecker $domains,
    ) {}

    public function findEmail(Company $company): ?string
    {
        $this->lastSource = null;
        $this->lastWebsite = null;

        foreach ($this->strategies() as $strategy) {
            $email = $strategy->find($company);

            if ($email === null || ! $this->extractor->isUsable($email)) {
                continue;
            }

            // A generated guess is only worth keeping if its domain can receive mail.
            if ($strategy->source() === EmailSource::Inferred && ! $this->domains->hasMx($email)) {
                continue;
            }

            $this->lastSource = $strategy->source();
            $this->lastWebsite = $strategy === $this->search ? $this->search->discoveredWebsite : null;

            return $email;
        }

        return null;
    }

    /**
     * Source of the address returned by the last findEmail() call.
     */
    public function lastSource(): ?EmailSource
    {
        return $this->lastSource;
    }

    /**
     * Whether the address equals one that pattern-based generation would produce
     * for this company. Used to classify legacy records as inferred.
     */
    public function isGeneratedPattern(Company $company, string $email): bool
    {
        return $this->pattern->isGenerated($company, $email);
    }

    public function enrichCompany(Company $company): bool
    {
        if ($company->email) {
            return true;
        }

        $email = $this->findEmail($company);
        if ($email === null) {
            return false;
        }

        $this->storeEmail($company, $email);

        return true;
    }

    /**
     * Enrich several companies.
     *
     * @param  callable(Company, bool, ?string, bool): void|null  $callback  company, success, email, hadEmailBefore
     * @return array{total: int, enriched: int, failed: int}
     */
    public function enrichCompanies(int $limit = 100, bool $skipExisting = true, ?callable $callback = null): array
    {
        $query = Company::query();

        if ($skipExisting) {
            $query->whereNull('email');
        }

        // Companies with a known website first: they are the ones that can succeed.
        $companies = $query->orderByRaw('website IS NULL')->limit($limit)->get();
        $results = ['total' => $companies->count(), 'enriched' => 0, 'failed' => 0];

        foreach ($companies as $company) {
            $hadEmailBefore = filled($company->email);
            $email = $company->email;
            $success = $hadEmailBefore;

            try {
                if (! $hadEmailBefore) {
                    $email = $this->findEmail($company);

                    if ($email !== null) {
                        $this->storeEmail($company, $email);
                        $success = true;
                        $results['enriched']++;
                    } else {
                        $results['failed']++;
                    }
                }
            } catch (Throwable) {
                $success = false;
                $email = null;
                $results['failed']++;
            }

            if ($callback) {
                $callback($company, $success, $email, $hadEmailBefore);
            }

            // Small delay to avoid rate limiting.
            usleep(max(0, (int) config('scraping.delay_ms')) * 1000);
        }

        return $results;
    }

    private function storeEmail(Company $company, string $email): void
    {
        $attributes = [
            'email' => $email,
            'email_source' => $this->lastSource ?? EmailSource::Observed,
            'email_found_at' => now(),
            'email_domain_has_mx' => $this->domains->hasMx($email),
        ];

        if ($this->lastWebsite !== null && blank($company->website)) {
            $attributes['website'] = $this->lastWebsite;
        }

        $company->update($attributes);
    }

    /**
     * @return list<EmailStrategy>
     */
    private function strategies(): array
    {
        $strategies = [$this->website, $this->search];

        if (config('enrichment.inferred_enabled')) {
            $strategies[] = $this->pattern;
        }

        return $strategies;
    }
}
