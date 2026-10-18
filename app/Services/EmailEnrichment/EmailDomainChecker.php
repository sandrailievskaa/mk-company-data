<?php

namespace App\Services\EmailEnrichment;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Checks that the domain of an address can receive mail at all (MX record).
 * This is a DNS lookup, not an SMTP mailbox verification.
 *
 * Lookups use DNS-over-HTTPS (fast and independent of the local resolver, whose
 * timeouts for non-existent domains can take many seconds); the system resolver
 * is the fallback.
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
        try {
            $response = Http::timeout(6)
                ->withHeaders(['Accept' => 'application/dns-json'])
                ->get((string) config('enrichment.doh_endpoint'), ['name' => $domain, 'type' => 'MX']);

            if ($response->successful()) {
                // Status 0 = NOERROR; an MX answer record has type 15.
                $answers = (array) $response->json('Answer', []);

                return $response->json('Status') === 0
                    && collect($answers)->contains(fn ($a) => ($a['type'] ?? null) === 15);
            }
        } catch (Throwable) {
            // fall through to the system resolver
        }

        return function_exists('checkdnsrr') && checkdnsrr($domain.'.', 'MX');
    }
}
