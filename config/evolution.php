<?php

declare(strict_types=1);

return [
    'base_url' => rtrim((string) env('EVOLUTION_API_BASE_URL', 'http://localhost:18080'), '/'),
    'api_key' => (string) env('EVOLUTION_API_KEY', ''),
    'timeout' => (int) env('EVOLUTION_API_TIMEOUT', 15),
    'webhook_url' => (string) env('EVOLUTION_WEBHOOK_URL', ''),
    'webhook_secret' => (string) env('EVOLUTION_WEBHOOK_SECRET', ''),
    'webhook_secret_header' => (string) env('EVOLUTION_WEBHOOK_SECRET_HEADER', 'x-evolution-secret'),
    'webhook_events' => [
        'QRCODE_UPDATED',
        'CONNECTION_UPDATE',
        'MESSAGES_UPSERT',
        'SEND_MESSAGE',
    ],
    'otp_instance_name' => (string) env('EVOLUTION_OTP_INSTANCE_NAME', ''),
    'otp_instance_token' => (string) env('EVOLUTION_OTP_INSTANCE_TOKEN', ''),
    'otp_message_template' => (string) env('EVOLUTION_OTP_MESSAGE_TEMPLATE', 'Your Najaah OTP is {{otp}}'),
];
