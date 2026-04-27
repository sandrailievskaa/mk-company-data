<?php

namespace App\Console\Commands;

use App\Ai\Agents\ValidationAgent;
use App\Models\Company;
use App\Services\AiUsage\AiUsageLogSource;
use App\Services\AiUsage\AiUsageRecorder;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class DetectInvalidCompaniesCommand extends Command
{
    protected $signature = 'app:detect-invalid-companies';

    protected $description = 'Use AI to flag duplicate/inactive/inconsistent companies';

    public function handle(): int
    {
        $this->configureSqliteForConcurrency();

        $total = Company::count();
        if ($total === 0) {
            $this->warn('No companies found.');

            return Command::SUCCESS;
        }

        $this->info('Starting AI data-quality detection (batch size: 20)...');

        $processed = 0;
        $flagged = 0;

        Company::query()
            ->select(['id', 'name', 'sector', 'city', 'address', 'phone', 'email'])
            ->orderBy('id')
            ->chunkById(20, function ($companies) use (&$processed, &$flagged) {
                $processed += $companies->count();

                $payload = $companies->map(function (Company $c) {
                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'sector' => $c->sector?->value ?? $c->sector,
                        'city' => $c->city,
                        'address' => $c->address,
                        'phone' => $c->phone,
                        'email' => $c->email,
                    ];
                })->values()->all();

                $prompt = $this->buildPrompt($payload);

                try {
                    $agent = ValidationAgent::make();
                    $response = $agent->prompt($prompt);

                    app(AiUsageRecorder::class)->record(
                        $response,
                        AiUsageLogSource::VALIDATION,
                        null
                    );

                    $raw = (string) ($response['issues_json'] ?? '');
                    $issues = json_decode($raw, true);

                    if (! is_array($issues)) {
                        $this->warn('AI returned invalid JSON; skipping this batch.');

                        return;
                    }

                    $validCompanyIds = $companies->pluck('id')->map(fn ($id) => (int) $id)->all();
                    $validIdSet = array_flip($validCompanyIds);

                    foreach ($issues as $issue) {
                        $companyId = (int) Arr::get($issue, 'company_id', 0);
                        $type = (string) Arr::get($issue, 'issue_type', '');
                        $reason = trim((string) Arr::get($issue, 'reason', ''));
                        $confidence = (float) Arr::get($issue, 'confidence', 0.0);

                        if ($companyId <= 0 || ! isset($validIdSet[$companyId])) {
                            continue;
                        }
                        if (! in_array($type, ['duplicate', 'inactive', 'inconsistent'], true)) {
                            continue;
                        }
                        if ($reason === '') {
                            continue;
                        }
                        if ($confidence < 0.0 || $confidence > 1.0) {
                            $confidence = max(0.0, min(1.0, $confidence));
                        }

                        $note = sprintf('[AI %s | confidence=%.2f] %s', $type, $confidence, $reason);

                        $this->updateWithRetry($companyId, $type, $note);
                        $flagged++;
                    }
                } catch (Throwable $e) {
                    $this->warn('Batch failed: '.$e->getMessage());
                }
            });

        $this->info("Done. Processed: {$processed}. Flagged: {$flagged}.");

        return Command::SUCCESS;
    }

    private function buildPrompt(array $companies): string
    {
        return "Given this list of companies in JSON, identify which ones are likely:\n".
            "(a) duplicates of another company in the list (similar name, same city)\n".
            "(b) inactive (name suggests dissolved, temporary, or test entity)\n".
            "(c) inconsistent (phone/email format doesn't match a real business)\n".
            "Return a JSON array with: company_id, issue_type (duplicate|inactive|inconsistent), reason, confidence (0.0-1.0)\n\n".
            "Companies:\n".
            json_encode($companies, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function updateWithRetry(int $companyId, string $flag, string $note): void
    {
        $attempts = 8;
        $delayMs = 100;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                Company::whereKey($companyId)->update([
                    'data_quality_flag' => $flag,
                    'data_quality_note' => $note,
                ]);

                return;
            } catch (QueryException $e) {
                if (! str_contains(strtolower($e->getMessage()), 'database is locked') || $i === $attempts) {
                    throw $e;
                }

                usleep($delayMs * 1000);
                $delayMs = min($delayMs * 2, 2000);
            }
        }
    }

    private function configureSqliteForConcurrency(): void
    {
        try {
            if (DB::getDriverName() !== 'sqlite') {
                return;
            }

            DB::statement('PRAGMA busy_timeout = 10000');
            DB::statement('PRAGMA journal_mode = WAL');
        } catch (Throwable) {
            // Best-effort only.
        }
    }
}
