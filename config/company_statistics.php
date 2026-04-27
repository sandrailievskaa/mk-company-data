<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company statistics (paper Table II)
    |--------------------------------------------------------------------------
    |
    | Some metrics referenced by the paper are not derived from the current DB
    | schema (e.g. "Initial Email Coverage %" and scraping time per sector).
    | Configure them here so the `app:company-statistics` command can print a
    | complete Table II-style summary.
    |
    */

    // Initial email coverage before enrichment (percentage 0-100).
    'initial_email_coverage_percent' => 0.0,

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

    // Average scraping time per sector (seconds). Set to null to print "N/A".
    'average_scraping_time_per_sector_seconds' => null,
];
