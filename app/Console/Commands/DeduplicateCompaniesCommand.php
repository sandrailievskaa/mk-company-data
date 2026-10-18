<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\OfferTarget;
use App\Models\SentOffer;
use App\Support\CompanyNameNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeduplicateCompaniesCommand extends Command
{
    protected $signature = 'app:deduplicate-companies {--dry-run : Report what would be merged without writing}';

    protected $description = 'Fill normalized_name for all companies and merge records that share the same normalized name';

    /** Contact/profile fields copied into the kept record when it lacks them. */
    private const MERGEABLE = ['sector', 'city', 'address', 'phone', 'email', 'email_source', 'email_found_at'];

    public function handle(CompanyNameNormalizer $normalizer): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->fillNormalizedNames($normalizer, $dryRun);

        $groups = $dryRun
            ? $this->duplicateGroupsInMemory($normalizer)
            : Company::query()->orderBy('id')->get()->groupBy('normalized_name')->filter(fn (Collection $g) => $g->count() > 1);

        $removed = 0;
        foreach ($groups as $group) {
            $removed += $group->count() - 1;

            if (! $dryRun) {
                $this->merge($group);
            } elseif ($this->getOutput()->isVerbose()) {
                $this->line($group->pluck('name')->implode('  |  '));
            }
        }

        $total = Company::count();
        $this->table(['Metric', 'Value'], [
            ['Companies before', $total + ($dryRun ? 0 : $removed)],
            ['Duplicate groups', $groups->count()],
            ['Records '.($dryRun ? 'that would be removed' : 'removed'), $removed],
            ['Companies after', $total - ($dryRun ? $removed : 0)],
        ]);
        $this->info($dryRun ? 'Dry run: nothing was written.' : 'Deduplication complete.');

        return Command::SUCCESS;
    }

    private function fillNormalizedNames(CompanyNameNormalizer $normalizer, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        Company::query()->chunkById(500, function ($companies) use ($normalizer) {
            foreach ($companies as $company) {
                $normalized = $normalizer->normalize($company->name);
                if ($company->normalized_name !== $normalized) {
                    $company->forceFill(['normalized_name' => $normalized])->saveQuietly();
                }
            }
        });
    }

    /**
     * @return Collection<string, Collection<int, Company>>
     */
    private function duplicateGroupsInMemory(CompanyNameNormalizer $normalizer): Collection
    {
        return Company::query()->orderBy('id')->get()
            ->groupBy(fn (Company $c) => $normalizer->normalize($c->name))
            ->filter(fn (Collection $g) => $g->count() > 1);
    }

    /**
     * Keep the most complete record, fold the others into it and re-point their relations.
     *
     * @param  Collection<int, Company>  $group
     */
    private function merge(Collection $group): void
    {
        DB::transaction(function () use ($group) {
            /** @var Company $keeper */
            $keeper = $group->sortByDesc(fn (Company $c) => [filled($c->email), $c->scrape_count, -$c->id])->first();
            $others = $group->reject(fn (Company $c) => $c->id === $keeper->id);

            foreach ($others as $other) {
                $fill = [];
                foreach (self::MERGEABLE as $field) {
                    if (blank($keeper->getRawOriginal($field)) && filled($other->getRawOriginal($field))) {
                        $fill[$field] = $other->getRawOriginal($field);
                    }
                }

                // Free the unique e-mail before it moves to the kept record.
                if (array_key_exists('email', $fill)) {
                    $other->forceFill(['email' => null])->saveQuietly();
                }

                $keeper->forceFill($fill + [
                    'scrape_count' => max($keeper->scrape_count, $other->scrape_count),
                ])->saveQuietly();

                $this->repointRelations($other, $keeper);
                $other->delete();
            }
        });
    }

    private function repointRelations(Company $from, Company $to): void
    {
        DB::table('offers')->where('company_id', $from->id)->update(['company_id' => $to->id]);

        foreach ([OfferTarget::class, SentOffer::class] as $model) {
            $model::query()->where('company_id', $from->id)->get()->each(function ($row) use ($to, $model) {
                $exists = $model::query()
                    ->where('offer_id', $row->offer_id)
                    ->where('company_id', $to->id)
                    ->exists();

                $exists ? $row->delete() : $row->update(['company_id' => $to->id]);
            });
        }
    }
}
