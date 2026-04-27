<?php

namespace App\Services\AiUsage;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiCreditsDisplay
{
    /**
     * @return array{
     *   used_credits: int,
     *   budget_credits: int,
     *   used_tokens: int,
     *   total_tokens: int,
     *   period_label: string,
     *   by_source: array<string, int>,
     *   has_legacy: bool
     * }
     */
    public function forCurrentMonth(): array
    {
        $start = now()->startOfMonth()->locale(str_replace('-', '_', (string) app()->getLocale()));
        $end = now()->endOfMonth();

        $tokensPerCredit = (int) config('ai_credits.tokens_per_credit', 1);
        $tokensPerCredit = max(1, $tokensPerCredit);
        $budgetCredits = (int) config('ai_credits.monthly_credits', 10_000);
        $budgetCredits = max(1, $budgetCredits);
        $totalTokenBudget = $budgetCredits * $tokensPerCredit;

        $fromLogs = $this->totalsFromUsageLogs($start, $end);
        $includeLegacy = (bool) config('ai_credits.include_legacy_conversation', true);
        $fromLegacy = $includeLegacy
            ? $this->totalsFromAgentConversationTable($start, $end)
            : ['total_tokens' => 0, 'by_source' => []];

        $usedTokens = $fromLogs['total_tokens'] + $fromLegacy['total_tokens'];
        $bySource = $fromLogs['by_source'];
        foreach ($fromLegacy['by_source'] as $key => $n) {
            $bySource[$key] = ($bySource[$key] ?? 0) + $n;
        }
        $usedCredits = (int) floor($usedTokens / $tokensPerCredit);

        return [
            'used_credits' => $usedCredits,
            'budget_credits' => $budgetCredits,
            'used_tokens' => (int) $usedTokens,
            'total_tokens' => $totalTokenBudget,
            'period_label' => $start->translatedFormat('F Y'),
            'by_source' => $bySource,
            'has_legacy' => $fromLegacy['total_tokens'] > 0,
        ];
    }

    /**
     * @return array{total_tokens: int, by_source: array<string, int>}
     */
    private function totalsFromUsageLogs(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        if (! Schema::hasTable('ai_usage_logs')) {
            return ['total_tokens' => 0, 'by_source' => []];
        }

        $rows = DB::table('ai_usage_logs')
            ->selectRaw('source, sum(total_tokens) as t')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('source')
            ->pluck('t', 'source')
            ->all();

        $by = [];
        $sum = 0;
        foreach ($rows as $source => $n) {
            $n = (int) $n;
            $by[(string) $source] = $n;
            $sum += $n;
        }

        return [
            'total_tokens' => $sum,
            'by_source' => $by,
        ];
    }

    /**
     * Legacy Laravel AI DB storage (when conversations were recorded).
     *
     * @return array{total_tokens: int, by_source: array<string, int>}
     */
    private function totalsFromAgentConversationTable(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        if (! Schema::hasTable('agent_conversation_messages')) {
            return ['total_tokens' => 0, 'by_source' => []];
        }

        $by = [
            AiUsageLogSource::OFFER => 0,
            AiUsageLogSource::RECOMMENDATION => 0,
            AiUsageLogSource::VALIDATION => 0,
        ];
        $total = 0;

        DB::table('agent_conversation_messages')
            ->select(['agent', 'usage'])
            ->where('role', 'assistant')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('id')
            ->chunk(200, function ($rows) use (&$by, &$total) {
                foreach ($rows as $row) {
                    $u = $this->decodeUsageJson($row->usage);
                    if ($u === 0) {
                        continue;
                    }
                    $src = $this->mapAgentClassToSource($row->agent);
                    if ($src !== null) {
                        $by[$src] = ($by[$src] ?? 0) + $u;
                    }
                    $total += $u;
                }
            });

        $by = array_filter($by, fn (int $n) => $n > 0);

        return [
            'total_tokens' => $total,
            'by_source' => $by,
        ];
    }

    private function decodeUsageJson(mixed $json): int
    {
        if ($json === null || $json === '' || $json === '[]') {
            return 0;
        }
        $data = is_string($json) ? json_decode($json, true) : (array) $json;
        if (! is_array($data)) {
            return 0;
        }

        $pt = (int) ($data['prompt_tokens'] ?? 0);
        $ct = (int) ($data['completion_tokens'] ?? 0);
        $cw = (int) ($data['cache_write_input_tokens'] ?? 0);
        $cr = (int) ($data['cache_read_input_tokens'] ?? 0);
        $rt = (int) ($data['reasoning_tokens'] ?? 0);

        return $pt + $ct + $cw + $cr + $rt;
    }

    private function mapAgentClassToSource(string $agentClass): ?string
    {
        return match ($agentClass) {
            \App\Ai\Agents\OfferAgent::class => AiUsageLogSource::OFFER,
            \App\Ai\Agents\RecommendationAgent::class => AiUsageLogSource::RECOMMENDATION,
            \App\Ai\Agents\ValidationAgent::class => AiUsageLogSource::VALIDATION,
            default => null,
        };
    }
}
