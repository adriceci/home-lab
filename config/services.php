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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'virustotal' => [
        'api_key' => env('VIRUSTOTAL_API_KEY'),
        'base_url' => env('VIRUSTOTAL_BASE_URL', 'https://www.virustotal.com/api/v3'),
        'timeout' => env('VIRUSTOTAL_TIMEOUT', 30),
        'max_retries' => env('VIRUSTOTAL_MAX_RETRIES', 3),
        'retry_delay' => env('VIRUSTOTAL_RETRY_DELAY', 1000), // milliseconds
    ],

];
