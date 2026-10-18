<?php

namespace App\Console\Commands;

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\EmailDomainChecker;
use Illuminate\Console\Command;

class VerifyEmailDomainsCommand extends Command
{
    protected $signature = 'app:verify-email-domains
                            {--clear-invalid-inferred : Remove pattern-generated e-mails whose domain has no MX record}';

    protected $description = 'Check that the domain of every stored e-mail can receive mail (MX lookup)';

    public function handle(EmailDomainChecker $checker): int
    {
        $clear = (bool) $this->option('clear-invalid-inferred');
        $stats = ['checked' => 0, 'with_mx' => 0, 'without_mx' => 0, 'cleared' => 0];

        Company::query()
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(200, function ($companies) use ($checker, $clear, &$stats) {
                foreach ($companies as $company) {
                    $hasMx = $checker->hasMx((string) $company->email);
                    $stats['checked']++;
                    $stats[$hasMx ? 'with_mx' : 'without_mx']++;

                    if ($clear && ! $hasMx && $company->email_source === EmailSource::Inferred) {
                        $company->forceFill(['email' => null, 'email_source' => null, 'email_found_at' => null, 'email_domain_has_mx' => null])->saveQuietly();
                        $stats['cleared']++;

                        continue;
                    }

                    $company->forceFill(['email_domain_has_mx' => $hasMx])->saveQuietly();
                }
            });

        $this->table(['Metric', 'Value'], collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all());

        return Command::SUCCESS;
    }
}
