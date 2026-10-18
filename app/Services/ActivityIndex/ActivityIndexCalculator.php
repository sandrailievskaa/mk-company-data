<?php

namespace App\Services\ActivityIndex;

use App\Enums\EmailSource;
use App\Models\Company;
use App\Services\EmailEnrichment\EmailExtractor;
use Illuminate\Support\Facades\DB;

/**
 * Computes the activity index: AI = w1*E + w2*P + w3*F.
 * See config/activity_index.php for the definition of each component.
 */
class ActivityIndexCalculator
{
    public function __construct(private readonly EmailExtractor $extractor) {}

    /**
     * Frequency bounds over the whole dataset, needed to normalize F.
     */
    public function frequencyScale(): FrequencyScale
    {
        return new FrequencyScale(
            (int) (Company::query()->min('scrape_count') ?? 0),
            (int) (Company::query()->max('scrape_count') ?? 0),
        );
    }

    /**
     * @return array{email: float, presence: float, frequency: float}
     */
    public function components(Company $company, FrequencyScale $scale): array
    {
        return [
            'email' => $this->emailComponent($company),
            'presence' => $this->presenceComponent($company),
            'frequency' => $scale->normalize((int) $company->scrape_count),
        ];
    }

    public function score(Company $company, FrequencyScale $scale): float
    {
        $c = $this->components($company, $scale);
        $w = config('activity_index.weights');

        $score = $w['email'] * $c['email'] + $w['presence'] * $c['presence'] + $w['frequency'] * $c['frequency'];

        return round(max(0.0, min(1.0, $score)), 6);
    }

    /**
     * Number of distinct public sources the company is present in:
     * the zk.mk registry listing (always), its own website, and CompanyWall.
     */
    public function sourcesCount(Company $company): int
    {
        $hasWebsite = filled($company->website)
            || in_array($company->email_source, [EmailSource::Website, EmailSource::Search], true);

        return 1 + ($hasWebsite ? 1 : 0) + (filled($company->companywall_url) ? 1 : 0);
    }

    /**
     * Recalculate and persist the index (and sources_count) for every company.
     *
     * @return array{count: int, min: float, max: float, avg: float}
     */
    public function recalculateAll(): array
    {
        $scale = $this->frequencyScale();
        $count = 0;
        $sum = 0.0;
        $min = null;
        $max = null;

        Company::query()
            ->select(['id', 'email', 'email_source', 'scrape_count', 'website', 'companywall_url'])
            ->orderBy('id')
            ->chunkById(500, function ($companies) use ($scale, &$count, &$sum, &$min, &$max) {
                DB::transaction(function () use ($companies, $scale, &$count, &$sum, &$min, &$max) {
                    foreach ($companies as $company) {
                        $score = $this->score($company, $scale);

                        Company::query()->whereKey($company->id)->update([
                            'activity_index' => $score,
                            'sources_count' => $this->sourcesCount($company),
                        ]);

                        $count++;
                        $sum += $score;
                        $min = $min === null ? $score : min($min, $score);
                        $max = $max === null ? $score : max($max, $score);
                    }
                });
            });

        return [
            'count' => $count,
            'min' => (float) $min,
            'max' => (float) $max,
            'avg' => $count > 0 ? $sum / $count : 0.0,
        ];
    }

    private function emailComponent(Company $company): float
    {
        if (! $this->extractor->isUsable($company->email)) {
            return 0.0;
        }

        if ($company->email_source === EmailSource::Inferred && ! config('activity_index.inferred_email_counts')) {
            return 0.0;
        }

        return 1.0;
    }

    private function presenceComponent(Company $company): float
    {
        $max = max(1, (int) config('activity_index.max_sources'));

        return min($this->sourcesCount($company), $max) / $max;
    }
}
