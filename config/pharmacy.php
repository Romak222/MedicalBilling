<?php

return [
    'version' => env('APP_VERSION', '0.5.0-phase5'),
    'store_code' => env('PHARMACY_STORE_CODE', 'LOCAL-DEV'),
    'deployment_mode' => env('PHARMACY_DEPLOYMENT_MODE', 'single-computer'),
    'online_integrations_enabled' => env('PHARMACY_ONLINE_INTEGRATIONS_ENABLED', false),

    'paths' => [
        'app_data' => env('PHARMACY_APP_DATA_PATH'),
        'backup' => env('PHARMACY_BACKUP_PATH'),
    ],

    'nativephp' => [
        'window_title' => env('NATIVEPHP_WINDOW_TITLE', env('APP_NAME', 'MedStore')),
        'window_width' => (int) env('NATIVEPHP_WINDOW_WIDTH', 1366),
        'window_height' => (int) env('NATIVEPHP_WINDOW_HEIGHT', 768),
    ],
];
