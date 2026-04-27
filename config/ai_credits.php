<?php

return [

    /*
    | Monthly “credit” budget for the admin sidebar meter.
    | One credit = `tokens_per_credit` total tokens (sum of all usage fields
    | from the AI response: prompt, completion, cache, reasoning).
    */
    'monthly_credits' => (int) env('AI_CREDITS_BUDGET', 10_000),

    'tokens_per_credit' => max(1, (int) env('AI_TOKENS_PER_CREDIT', 1_000)),

    /*
    | If true, also counts tokens from legacy agent_conversation_messages
    | (Laravel AI DB store). Set false to rely only on ai_usage_logs
    | and avoid any chance of double counting if both systems ever overlap.
    */
    'include_legacy_conversation' => (bool) env('AI_INCLUDE_LEGACY_AGENT_MESSAGES', true),

];
