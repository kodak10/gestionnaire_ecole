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
    
    // Configuration Orange SMS API
    'orange' => [
        'api_key' => env('ORANGE_API_KEY'),
        'api_url' => env('ORANGE_API_URL', 'https://api.orange.com/smsmessaging/v1/outbound/tel%3A%2B2250000/requests'),
        'sender_name' => env('ORANGE_SENDER_NAME', 'MonEcole'),
        'sender_address' => env('ORANGE_SENDER_ADDRESS', 'tel:+2250000'),
        'authorization_header' => env('ORANGE_AUTHORIZATION_HEADER'),
        'client_id' => env('ORANGE_CLIENT_ID'),
        'client_secret' => env('ORANGE_CLIENT_SECRET'),
        'application_id' => env('ORANGE_APPLICATION_ID'),
        'token_url' => env('ORANGE_TOKEN_URL', 'https://api.orange.com/oauth/v2/token'),
    ],

    // Gardez QuickNotify si vous voulez une option de fallback
    'quick_notify' => [
        'api_key' => env('QUICK_NOTIFY_API_KEY'),
        'api_url' => env('QUICK_NOTIFY_API_URL', 'https://api.quick-notify.pro/api/messages/request'),
        'sender_name' => env('QUICK_NOTIFY_SENDER_NAME', 'MonEcole'),
        'webhook_url' => env('QUICK_NOTIFY_WEBHOOK_URL'),
    ],

];