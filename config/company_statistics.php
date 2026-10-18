<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company statistics (paper Table II)
    |--------------------------------------------------------------------------
    |
    | All Table II metrics are computed from the database (see StatisticsService):
    | initial coverage = e-mails taken from the registry listing, duplicate
    | reduction and scraping time = from the `scrape_runs` table.
    |
    */

    // «Активни» на дашборд: `activity_index` поголемо од вредноста (0.0–1.0, обично 0,5).
    'active_activity_index_threshold' => (function (): float {
        $v = env('ACTIVE_ACTIVITY_INDEX_THRESHOLD', '0.5');

        return is_numeric($v) ? (float) $v : 0.5;
    })(),

    /*
    | Optional: duplicate “rate” baseline (0–100) for the dashboard card delta.
    | Duplicate rate = (companies with scrape_count > 1) / (total) * 100.
    | If null, the dashboard only shows the current rate (no сравнение).
    */
    'baseline_duplicate_rate_percent' => ($__dup = env('BASELINE_DUPLICATE_RATE')) === null || $__dup === ''
        ? null
        : (float) $__dup,
];
