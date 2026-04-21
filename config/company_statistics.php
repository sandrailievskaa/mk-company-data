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

    // Average scraping time per sector (seconds). Set to null to print "N/A".
    'average_scraping_time_per_sector_seconds' => null,
];

