<?php

namespace App\Support;

/**
 * Single source of truth for company-name normalization.
 *
 * Steps (as described in the paper): lowercase, transliterate Macedonian
 * Cyrillic to Latin, drop special characters and strip legal-form suffixes
 * (DOO, DOOEL, AD, LLC, ...). Used for duplicate detection and domain guessing.
 */
class CompanyNameNormalizer
{
    /** Legal-form tokens removed from the start or end of a name. */
    private const LEGAL_TOKENS = [
        'doo', 'dooel', 'ad', 'dd', 'ddd', 'jsd', 'llc', 'ltd', 'inc', 'corp', 'corporation', 'gmbh', 'mkd',
    ];

    /**
     * Collision-free transliteration used for duplicate detection
     * (so that e.g. "Жана" and "Зана" stay different).
     */
    private const TRANSLITERATION = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'ѓ' => 'gj',
        'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'ѕ' => 'dz', 'и' => 'i', 'ј' => 'j',
        'к' => 'k', 'л' => 'l', 'љ' => 'lj', 'м' => 'm', 'н' => 'n', 'њ' => 'nj',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'ќ' => 'kj',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'џ' => 'dzh',
        'ш' => 'sh', 'ђ' => 'dj', 'ћ' => 'ch',
    ];

    /** Letters that the common domain-style spelling flattens (ж => z, ч => c, ш => s ...). */
    private const DOMAIN_OVERRIDES = [
        'ж' => 'z', 'ч' => 'c', 'ш' => 's', 'ћ' => 'c',
    ];

    /**
     * Normalized name with single spaces, e.g. "Нова Градба ДООЕЛ" => "nova gradba".
     */
    public function normalize(?string $name): string
    {
        return $this->process($name, self::TRANSLITERATION);
    }

    /**
     * @param  array<string, string>  $map
     */
    private function process(?string $name, array $map): string
    {
        $name = mb_strtolower(trim((string) $name), 'UTF-8');
        $name = strtr($name, $map);
        $name = str_replace('.', '', $name);
        $name = (string) preg_replace('/[^a-z0-9]+/u', ' ', $name);

        $tokens = array_values(array_filter(explode(' ', $name), fn (string $t): bool => $t !== ''));

        $isLegal = fn (string $t): bool => in_array($t, self::LEGAL_TOKENS, true);

        while (count($tokens) > 1 && $isLegal($tokens[0])) {
            array_shift($tokens);
        }
        while (count($tokens) > 1 && ($isLegal(end($tokens)) || end($tokens) === 'mk')) {
            array_pop($tokens);
        }

        return implode(' ', $tokens);
    }

    /**
     * Normalized name without spaces; the base for domain/e-mail guessing.
     */
    public function compact(?string $name): string
    {
        return str_replace(' ', '', $this->process($name, self::DOMAIN_OVERRIDES + self::TRANSLITERATION));
    }
}
