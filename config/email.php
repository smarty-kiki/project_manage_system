<?php

return [
    'driver' => env('EMAIL_DRIVER', 'log'),
    'from' => env('EMAIL_FROM', 'noreply@localhost'),
    'from_name' => env('EMAIL_FROM_NAME', 'Project Manager'),
    'log_path' => env('EMAIL_LOG_PATH', '/tmp/email.log'),
    'smtp' => [
        'host' => env('SMTP_HOST', ''),
        'port' => (int)env('SMTP_PORT', 25),
        'username' => env('SMTP_USERNAME', ''),
        'password' => env('SMTP_PASSWORD', ''),
        'encryption' => env('SMTP_ENCRYPTION', ''),
    ],
];
