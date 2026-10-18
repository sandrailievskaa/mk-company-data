<?php

namespace App\Console\Commands;

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\EmailEnrichmentService;
use Illuminate\Console\Command;

class BackfillEmailSourceCommand extends Command
{
    protected $signature = 'app:backfill-email-source {--dry-run : Only report, do not write}';

    protected $description = 'Classify legacy e-mails: pattern-generated ones become "inferred", the rest "observed"';

    public function handle(EmailEnrichmentService $enrichment): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = [EmailSource::Inferred->value => 0, EmailSource::Observed->value => 0];

        Company::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNull('email_source')
            ->orderBy('id')
            ->chunkById(200, function ($companies) use ($enrichment, $dryRun, &$counts) {
                foreach ($companies as $company) {
                    $source = $enrichment->isGeneratedPattern($company, (string) $company->email)
                        ? EmailSource::Inferred
                        : EmailSource::Observed;

                    $counts[$source->value]++;

                    if (! $dryRun) {
                        $company->forceFill(['email_source' => $source])->saveQuietly();
                    }
                }
            });

        $this->table(['Source', 'Companies'], collect($counts)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info($dryRun ? 'Dry run: nothing was written.' : 'Backfill complete.');

        return Command::SUCCESS;
    }
}
