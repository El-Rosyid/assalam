<?php

return [
    'api_key' => env('WHATSAPP_API_KEY', 'your_api_key_here'),
    'api_url' => env('WHATSAPP_API_URL', 'https://api.whatsapp.com/send'),
    'default_sender' => env('WHATSAPP_DEFAULT_SENDER', 'your_default_sender_number'),
    'timeout' => env('WHATSAPP_TIMEOUT', 30),
    'retry_attempts' => env('WHATSAPP_RETRY_ATTEMPTS', 3),
];