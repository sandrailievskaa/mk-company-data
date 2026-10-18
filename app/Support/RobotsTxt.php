<?php

namespace App\Support;

/**
 * Minimal robots.txt parser: Disallow/Allow rules and Crawl-delay for the "*" agent.
 * The longest matching rule wins; Allow wins ties (as in the common robots.txt spec).
 */
class RobotsTxt
{
    /** @var list<array{type: string, path: string}> */
    private array $rules = [];

    private ?float $crawlDelay = null;

    public function __construct(string $body = '')
    {
        $this->parse($body);
    }

    public function isAllowed(string $path): bool
    {
        $path = '/'.ltrim((string) parse_url($path, PHP_URL_PATH), '/');

        $best = null;
        foreach ($this->rules as $rule) {
            if ($rule['path'] === '' || ! str_starts_with($path, $rule['path'])) {
                continue;
            }

            if ($best === null
                || strlen($rule['path']) > strlen($best['path'])
                || (strlen($rule['path']) === strlen($best['path']) && $rule['type'] === 'allow')) {
                $best = $rule;
            }
        }

        return $best === null || $best['type'] === 'allow';
    }

    public function crawlDelaySeconds(): ?float
    {
        return $this->crawlDelay;
    }

    private function parse(string $body): void
    {
        $appliesToAll = false;
        $sawAgent = false;

        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            $line = trim((string) preg_replace('/#.*/', '', $line));
            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                // A new group starts after rules; consecutive agent lines share a group.
                $appliesToAll = $sawAgent ? $appliesToAll || $value === '*' : $value === '*';
                $sawAgent = true;

                continue;
            }

            $sawAgent = false;
            if (! $appliesToAll) {
                continue;
            }

            match ($field) {
                'disallow' => $this->rules[] = ['type' => 'disallow', 'path' => $value],
                'allow' => $this->rules[] = ['type' => 'allow', 'path' => $value],
                'crawl-delay' => $this->crawlDelay = is_numeric($value) ? (float) $value : null,
                default => null,
            };
        }
    }
}
