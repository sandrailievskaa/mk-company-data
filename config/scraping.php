<?php

return [

    /*
    | Base URL of the public registry that is scraped.
    */
    'base_url' => env('SCRAPER_BASE_URL', 'https://zk.mk'),

    /*
    | CompanyWall public company profiles (registry identifiers).
    */
    'companywall_url' => env('COMPANYWALL_BASE_URL', 'https://www.companywall.com.mk'),

    /*
    | Delay between two HTTP requests, in milliseconds (the paper uses ~0.3 s).
    | Note: zk.mk/robots.txt advertises "Crawl-delay: 5". Raise this value to honour
    | it fully (SCRAPER_DELAY_MS=5000); each run logs a warning when it is lower.
    */
    'delay_ms' => (int) env('SCRAPER_DELAY_MS', 300),

    /*
    | HTTP timeouts in seconds (the paper reports configurable 5-15 s timeouts).
    */
    'timeout' => (int) env('SCRAPER_TIMEOUT', 15),
    'max_duration' => (int) env('SCRAPER_MAX_DURATION', 60),

    'retries' => (int) env('SCRAPER_RETRIES', 5),

    /*
    | Skip paths that robots.txt disallows for all user agents.
    */
    'respect_robots' => (bool) env('SCRAPER_RESPECT_ROBOTS', true),

    'user_agent' => env(
        'SCRAPER_USER_AGENT',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ),
];
