<?php

namespace App\Services\EmailEnrichment\Strategies;

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\DomainGuesser;

/**
 * Strategy 3 (fallback): generate info@<guessed domain>. The result is never
 * verified, therefore it is always recorded as inferred.
 */
class PatternStrategy implements EmailStrategy
{
    public const LOCAL_PART = 'info';

    public function __construct(private readonly DomainGuesser $domains) {}

    public function source(): EmailSource
    {
        return EmailSource::Inferred;
    }

    public function find(Company $company): ?string
    {
        $domain = $this->domains->preferredDomain($company);

        return $domain === null ? null : self::LOCAL_PART.'@'.$domain;
    }

    /**
     * Whether the address is one that find() could have generated for this company.
     */
    public function isGenerated(Company $company, string $email): bool
    {
        $email = strtolower(trim($email));
        if (! str_contains($email, '@')) {
            return false;
        }

        [$local, $domain] = explode('@', $email, 2);

        return $local === self::LOCAL_PART && in_array($domain, $this->domains->domainsFor($company), true);
    }
}
