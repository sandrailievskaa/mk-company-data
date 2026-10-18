<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\EmailEnrichment\EmailExtractor;
use Illuminate\Console\Command;

class CleanJunkEmailsCommand extends Command
{
    protected $signature = 'app:clean-junk-emails {--dry-run : Only list what would be cleared}';

    protected $description = 'Clear stored e-mails that are not company contacts (tracker, schema, asset or placeholder addresses)';

    public function handle(EmailExtractor $extractor): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $cleared = 0;

        Company::query()
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(500, function ($companies) use ($extractor, $dryRun, &$cleared) {
                foreach ($companies as $company) {
                    if ($extractor->isUsable($company->email)) {
                        continue;
                    }

                    $cleared++;
                    $this->line(sprintf('%s: %s', $company->name, $company->email));

                    if (! $dryRun) {
                        $company->forceFill(['email' => null, 'email_source' => null, 'email_found_at' => null])->saveQuietly();
                    }
                }
            });

        $this->info(($dryRun ? 'Would clear ' : 'Cleared ').$cleared.' e-mail(s).');

        return Command::SUCCESS;
    }
}
