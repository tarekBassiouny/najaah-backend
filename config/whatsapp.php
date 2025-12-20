<?php

return [
    'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
    'api_version' => env('WHATSAPP_API_VERSION', 'v19.0'),
    'otp_template' => env('WHATSAPP_OTP_TEMPLATE', ''),
];
