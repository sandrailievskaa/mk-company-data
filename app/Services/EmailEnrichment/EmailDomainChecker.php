<?php

namespace App\Services\EmailEnrichment;

/**
 * Checks that the domain of an address can receive mail at all (MX record).
 * This is a DNS lookup, not an SMTP mailbox verification.
 */
class EmailDomainChecker
{
    /** @var array<string, bool> */
    private array $cache = [];

    public function hasMx(string $email): bool
    {
        $domain = strtolower(trim(substr(strrchr($email, '@') ?: '', 1)));

        if ($domain === '') {
            return false;
        }

        return $this->cache[$domain] ??= $this->lookup($domain);
    }

    protected function lookup(string $domain): bool
    {
        return function_exists('checkdnsrr') && checkdnsrr($domain.'.', 'MX');
    }
}
