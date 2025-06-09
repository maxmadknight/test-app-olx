<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | OLX Scraper Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the OLX price scraper.
    |
    */

    // Number of attempts to fetch a price before giving up
    'attempts' => env('OLX_FETCH_ATTEMPTS', 3),

    // Timeout in seconds for HTTP requests
    'timeout' => env('OLX_FETCH_TIMEOUT', 15),

    // Verification token expiration time in hours
    'token_expiration' => env('OLX_TOKEN_EXPIRATION', 24),

    // Supported OLX domains
    'domains' => [
        'olx.pl',
        'olx.ua',
        'olx.ro',
        'olx.bg',
        'olx.pt',
    ],

    // Queue for price check jobs
    'queue' => env('OLX_QUEUE', 'price-checks'),

    // Chunk size for processing advertisements
    'chunk_size' => env('OLX_CHUNK_SIZE', 100),
];
