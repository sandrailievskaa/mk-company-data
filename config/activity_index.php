<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activity index (heuristic, not a predictive model)
    |--------------------------------------------------------------------------
    |
    |   activity_index = w1 * E + w2 * P + w3 * F        (each component in [0, 1])
    |
    |   E  e-mail availability (binary)
    |   P  presence in several public sources, normalized by `max_sources`
    |   F  frequency of occurrence across scraped listings, min-max normalized
    |      on a logarithmic scale so a few extreme outliers do not flatten the rest
    |
    | The weights are set empirically as in the paper (0.5 / 0.3 / 0.2).
    |
    */

    'weights' => [
        'email' => (float) env('ACTIVITY_WEIGHT_EMAIL', 0.5),
        'presence' => (float) env('ACTIVITY_WEIGHT_PRESENCE', 0.3),
        'frequency' => (float) env('ACTIVITY_WEIGHT_FREQUENCY', 0.2),
    ],

    /*
    | Public sources a company can be observed in: the registry listing (always),
    | its own website, and search results. P = distinct sources / max_sources.
    */
    'max_sources' => (int) env('ACTIVITY_MAX_SOURCES', 3),

    /*
    | Whether a pattern-generated (inferred) address counts as "email available".
    | The paper's coverage figure includes them; set to false to rank only on
    | addresses that were actually found.
    */
    'inferred_email_counts' => (bool) env('ACTIVITY_INFERRED_EMAIL_COUNTS', true),
];
