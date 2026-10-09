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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
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

    'aba_payway' => [
        'base_url'         => env('ABA_PAYWAY_BASE_URL', 'https://checkout-sandbox.payway.com.kh'),
        'merchant_id'      => env('ABA_PAYWAY_MERCHANT_ID', 'ec479222'),
        // No credential fallback: missing credentials must fail closed.
        'api_key'          => env('ABA_PAYWAY_API_KEY'),
        'private_key'      => env('ABA_PAYWAY_RSA_PRIVATE_KEY'),
        'private_key_path' => env('ABA_PAYWAY_RSA_PRIVATE_KEY_PATH', storage_path('app/keys/aba_private.pem')),
        'webhook_url'      => env('ABA_PAYWAY_WEBHOOK_URL'),
    ],

];
