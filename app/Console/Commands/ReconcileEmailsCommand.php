<?php

namespace App\Console\Commands;

use App\Enums\EmailSource;
use App\Models\Company;
use Illuminate\Console\Command;

/**
 * Keeps only e-mails whose origin can be confirmed.
 *
 * An address that belongs to the company's own website (the website listed on its
 * zk.mk profile) is confirmed and recorded as "website". Older addresses of unknown
 * origin (found on guessed domains, or pattern-generated) cannot be confirmed and are
 * cleared so the enrichment can collect them again from the real website.
 * Manually entered and registry addresses are never touched.
 */
class ReconcileEmailsCommand extends Command
{
    protected $signature = 'app:reconcile-emails {--dry-run : Only report what would change}';

    protected $description = 'Confirm e-mails against the company website and clear those of unknown origin';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $stats = ['confirmed' => 0, 'cleared' => 0, 'kept' => 0];

        Company::query()
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(200, function ($companies) use ($dryRun, &$stats) {
                foreach ($companies as $company) {
                    $action = $this->decide($company);
                    $stats[$action]++;

                    if ($dryRun || $action === 'kept') {
                        continue;
                    }

                    $company->forceFill($action === 'confirmed'
                        ? ['email_source' => EmailSource::Website]
                        : ['email' => null, 'email_source' => null, 'email_found_at' => null, 'email_domain_has_mx' => null]
                    )->saveQuietly();
                }
            });

        $this->table(['Result', 'Companies'], collect($stats)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info($dryRun ? 'Dry run: nothing was written.' : 'Done.');

        return Command::SUCCESS;
    }

    /**
     * @return 'confirmed'|'cleared'|'kept'
     */
    public function decide(Company $company): string
    {
        if (in_array($company->email_source, [EmailSource::Manual, EmailSource::Registry], true)) {
            return 'kept';
        }

        if ($this->belongsToWebsite((string) $company->email, $company->website)) {
            return 'confirmed';
        }

        // Found on a page we cannot tie to the company, or merely generated.
        return in_array($company->email_source, [EmailSource::Observed, EmailSource::Inferred, null], true)
            ? 'cleared'
            : 'kept';
    }

    private function belongsToWebsite(string $email, ?string $website): bool
    {
        if (blank($website) || ! str_contains($email, '@')) {
            return false;
        }

        $mailDomain = strtolower(substr(strrchr($email, '@'), 1));
        $host = strtolower((string) parse_url(str_contains($website, '://') ? $website : 'http://'.$website, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);

        if ($host === '' || $mailDomain === '') {
            return false;
        }

        return $mailDomain === $host
            || str_ends_with($mailDomain, '.'.$host)
            || str_ends_with($host, '.'.$mailDomain);
    }
}
