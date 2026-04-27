<?php

namespace App\Services\AiUsage;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Usage;

class AiUsageRecorder
{
    public function record(AgentResponse $response, string $source, ?int $userId = null): void
    {
        $usage = $response->usage;
        $total = $this->totalTokens($usage);

        DB::table('ai_usage_logs')->insert([
            'user_id' => $userId,
            'source' => $source,
            'prompt_tokens' => $usage->promptTokens,
            'completion_tokens' => $usage->completionTokens,
            'cache_write_input_tokens' => $usage->cacheWriteInputTokens,
            'cache_read_input_tokens' => $usage->cacheReadInputTokens,
            'reasoning_tokens' => $usage->reasoningTokens,
            'total_tokens' => $total,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function totalTokens(Usage $usage): int
    {
        return $usage->promptTokens
            + $usage->completionTokens
            + $usage->cacheWriteInputTokens
            + $usage->cacheReadInputTokens
            + $usage->reasoningTokens;
    }
}
