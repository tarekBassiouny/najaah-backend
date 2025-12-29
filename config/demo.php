<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('DEMO_SEEDING_ENABLED', false),
    'super_admin' => [
        'name' => (string) env('DEMO_SUPER_ADMIN_NAME', 'System Admin'),
        'email' => (string) env('DEMO_SUPER_ADMIN_EMAIL', ''),
        'phone' => (string) env('DEMO_SUPER_ADMIN_PHONE', ''),
        'password' => (string) env('DEMO_SUPER_ADMIN_PASSWORD', ''),
    ],
];
