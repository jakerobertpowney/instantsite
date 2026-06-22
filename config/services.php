<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'pexels' => [
        'key' => env('PEXELS_API_KEY'),
    ],

    // ── Domain provider OAuth / API ────────────────────────────────────────────
    // Register a Cloudflare OAuth app at: https://dash.cloudflare.com/profile/api-tokens
    // Set the redirect URI to: {APP_URL}/dashboard/domain/cloudflare/callback
    'stannp' => [
        'key' => env('STANNP_API_KEY'),
    ],

    'cloudflare' => [
        'client_id'     => env('CLOUDFLARE_CLIENT_ID'),
        'client_secret' => env('CLOUDFLARE_CLIENT_SECRET'),
        'redirect'      => env('CLOUDFLARE_REDIRECT_URI'),
    ],

    // ── Ploi — automatic Let's Encrypt SSL for custom domains ───────────────────
    // Generate an API token at: https://ploi.io/profile/api-keys
    // server_id / site_id are the IDs of the server + site hosting this app in Ploi.
    // When these are unset, SSL provisioning is skipped (e.g. local dev).
    'ploi' => [
        'token'     => env('PLOI_API_TOKEN'),
        'server_id' => env('PLOI_SERVER_ID'),
        'site_id'   => env('PLOI_SITE_ID'),
    ],

];
