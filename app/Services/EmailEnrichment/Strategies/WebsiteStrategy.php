<?php

namespace App\Services\EmailEnrichment\Strategies;

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\DomainGuesser;
use App\Services\EmailEnrichment\SiteEmailFinder;

/**
 * Strategy 1: read the company's own website (taken from its public zk.mk profile)
 * and look for an address on the home page and the contact pages.
 *
 * Guessing domains from the company name is off by default (it is an assumption);
 * enable it with ENRICHMENT_GUESS_DOMAINS=true.
 */
class WebsiteStrategy implements EmailStrategy
{
    public function __construct(
        private readonly SiteEmailFinder $finder,
        private readonly DomainGuesser $domains,
    ) {}

    public function source(): EmailSource
    {
        return EmailSource::Website;
    }

    public function find(Company $company): ?string
    {
        if (filled($company->website)) {
            return $this->finder->find(SiteEmailFinder::variants((string) $company->website));
        }

        if (! config('enrichment.guess_domains')) {
            return null;
        }

        foreach ($this->domains->domainsFor($company) as $domain) {
            $email = $this->finder->find(SiteEmailFinder::variants($domain));
            if ($email !== null) {
                return $email;
            }
        }

        return null;
    }
}
