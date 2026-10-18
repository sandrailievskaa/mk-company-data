<?php

return [

    /*
    | Strategy 2 (search engine discovery). Search engines often block automated
    | queries, so this can be switched off without affecting the other strategies.
    */
    'search_enabled' => (bool) env('ENRICHMENT_SEARCH_ENABLED', true),

    /*
    | Guessing a company's domain from its name is an assumption, so it is off:
    | websites come from the company's zk.mk profile or from a search result whose
    | domain matches the name. Turn it on only for experiments.
    */
    'guess_domains' => (bool) env('ENRICHMENT_GUESS_DOMAINS', false),

    /*
    | Strategy 3 (pattern-based info@domain). The paper describes it as a fallback
    | that is marked as inferred; it is disabled by default because the address is
    | a guess. An inferred address is only kept when its domain has an MX record.
    */
    'inferred_enabled' => (bool) env('ENRICHMENT_INFERRED_ENABLED', false),

    /*
    | DNS-over-HTTPS endpoint used for MX lookups (JSON API).
    */
    'doh_endpoint' => env('ENRICHMENT_DOH_ENDPOINT', 'https://cloudflare-dns.com/dns-query'),

    /*
    | TLS verification for company websites. Provide a CA bundle path when the
    | PHP installation has none configured (e.g. Herd: ~/.config/herd/config/php/cacert.pem).
    */
    'verify_ssl' => (bool) env('ENRICHMENT_VERIFY_SSL', true),
    'ca_bundle' => env('ENRICHMENT_CA_BUNDLE'),

    /*
    | Request timeouts in seconds (the paper reports 5-15 s).
    */
    'timeouts' => [
        'default' => 15,
        'website' => 5,
        'search' => 10,
    ],

    'contact_paths' => [
        '/contact', '/kontakt', '/contact-us', '/kontaktirajte-ne', '/about', '/za-nas', '/info',
    ],

    /*
    | Addresses containing any of these fragments are never accepted (assets,
    | trackers, platform addresses, placeholders).
    */
    'blocked_fragments' => [
        'example.', 'test.com', 'noreply', 'no-reply', 'donotreply', 'cdn77.org', 'zk.mk',
        'facebook.com', 'twitter.com', 'linkedin.com', 'instagram.com', 'google.com',
        'sentry.io', 'sentry.', 'w3.org', 'schema.org', 'wixpress.com', 'wix.com',
        'godaddy.com', 'domainmarket.com', 'sedo.com', 'hugedomains.com', 'domain.com', 'yourdomain', 'email.com', 'your@', 'name@',
    ],

    'blocked_suffixes' => ['.png', '.jpg', '.jpeg', '.gif', '.svg', '.webp', '.css', '.js'],
];
