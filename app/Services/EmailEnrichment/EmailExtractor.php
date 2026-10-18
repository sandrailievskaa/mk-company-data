<?php

namespace App\Services\EmailEnrichment;

/**
 * Finds the most plausible contact address in a piece of HTML/text and decides
 * whether an address is usable at all.
 */
class EmailExtractor
{
    private const PATTERN = '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/';

    public function extract(string $text): ?string
    {
        if (! preg_match_all(self::PATTERN, $text, $matches)) {
            return null;
        }

        $candidates = [];
        foreach ($matches[0] as $email) {
            $email = strtolower(trim($email, ' .-'));
            if ($this->isUsable($email)) {
                $candidates[$email] = true;
            }
        }

        $candidates = array_keys($candidates);
        if ($candidates === []) {
            return null;
        }

        // Prefer Macedonian domains, then the first valid address.
        foreach ($candidates as $email) {
            if (str_ends_with($email, '.mk')) {
                return $email;
            }
        }

        return $candidates[0];
    }

    public function isUsable(?string $email): bool
    {
        if (blank($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $email = strtolower($email);

        foreach ((array) config('enrichment.blocked_fragments') as $fragment) {
            if (str_contains($email, $fragment)) {
                return false;
            }
        }

        foreach ((array) config('enrichment.blocked_suffixes') as $suffix) {
            if (str_ends_with($email, $suffix)) {
                return false;
            }
        }

        // Machine-generated local parts (hashes, tracking ids) are not company contacts.
        $local = explode('@', $email)[0];

        return ! preg_match('/^[a-f0-9]{16,}$/', $local);
    }
}
