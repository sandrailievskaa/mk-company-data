<?php

namespace App\Services\EmailEnrichment;

use App\Models\Company;
use App\Support\CompanyNameNormalizer;

/**
 * Builds the list of candidate domains for a company from its normalized name.
 */
class DomainGuesser
{
    public function __construct(private readonly CompanyNameNormalizer $normalizer) {}

    /**
     * @return list<string>
     */
    public function domainsFor(Company $company): array
    {
        $name = $this->normalizer->compact($company->name);
        if ($name === '') {
            return [];
        }

        $domains = ["{$name}.mk", "{$name}.com.mk", "{$name}.com"];

        // Long names are often shortened in domains.
        if (strlen($name) > 15) {
            $short = substr($name, 0, 12);
            $domains[] = "{$short}.mk";
            $domains[] = "{$short}.com.mk";
        }

        return array_values(array_unique($domains));
    }

    /**
     * The domain used for a pattern-based (inferred) address: prefer .mk, then .com.mk.
     */
    public function preferredDomain(Company $company): ?string
    {
        $domains = $this->domainsFor($company);

        foreach ($domains as $domain) {
            if (str_ends_with($domain, '.mk') && ! str_ends_with($domain, '.com.mk')) {
                return $domain;
            }
        }

        foreach ($domains as $domain) {
            if (str_ends_with($domain, '.com.mk')) {
                return $domain;
            }
        }

        return $domains[0] ?? null;
    }
}
