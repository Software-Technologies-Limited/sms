<?php

return [
    'default' => env('SMS_DRIVER', 'africastalking'),

    'drivers' => [
        'africastalking' => [
            'username' => env('AFRICASTALKING_USERNAME', 'sandbox'),
            'api_key' => env('AFRICASTALKING_API_KEY'),
            'from' => env('AFRICASTALKING_FROM'),
            'endpoint' => env('AFRICASTALKING_SMS_ENDPOINT', env('AFRICASTALKING_USERNAME', 'sandbox') === 'sandbox'
                ? 'https://api.sandbox.africastalking.com/version1/messaging'
                : 'https://api.africastalking.com/version1/messaging'),
            'timeout' => (int) env('AFRICASTALKING_TIMEOUT', 15),
            // Optional defaults that can be overridden per send() call.
            'bulk_mode' => env('AFRICASTALKING_BULK_MODE'),
        ],
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM'),
            'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
            'endpoint' => env('TWILIO_SMS_ENDPOINT', 'https://api.twilio.com/2010-04-01'),
            'timeout' => (int) env('TWILIO_TIMEOUT', 15),
        ],
    ],

    'templates' => [
        // 'compliance_notice' => 'Dear {name}, your status is {status}.',
    ],

    'load_migrations' => env('SMS_LOAD_MIGRATIONS', false),

    'logging' => [
        'enabled' => env('SMS_LOGGING', false),
        'table' => 'sms_logs',
    ],

    'webhook' => [
        'enabled' => env('SMS_WEBHOOK_ENABLED', false),
        'path' => env('SMS_WEBHOOK_PATH', 'sms/webhook'),
        'middleware' => ['api'],
    ],
];
