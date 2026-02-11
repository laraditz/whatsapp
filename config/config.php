<?php

return [
    'default' => env('WHATSAPP_ACCOUNT', 'default'),
    'api_version' => env('WHATSAPP_API_VERSION', 'v24.0'),
    'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),
    'account_driver' => env('WHATSAPP_ACCOUNT_DRIVER', 'config'),
    'webhook_path' => env('WHATSAPP_WEBHOOK_PATH', 'whatsapp/webhook'),

    'logging' => [
        'api_requests' => true,
        'messages' => true,
        'webhooks' => true,
        'templates' => true,
    ],

    'accounts' => [
        'default' => [
            'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
            'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
            'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
        ],
    ],
];
