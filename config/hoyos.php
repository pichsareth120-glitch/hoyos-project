<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Grace Period (Minutes)
    |--------------------------------------------------------------------------
    |
    | The duration in minutes after a KHQR payment expires during which
    | the customer can still retry/regenerate payment before the order
    | is automatically cancelled and stock is restored.
    |
    */
    'payment_grace_minutes' => (int) env('HOYOS_PAYMENT_GRACE_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | When demo mode is enabled, the store operates in a public demonstration
    | state: simulated payment is the only permitted payment method, external
    | calls to payment gateways are blocked, and clear demo notices are
    | displayed across checkout and payment screens.
    |
    */
    'demo_mode' => (bool) env('HOYOS_DEMO_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Allow Sandbox Payment Simulation
    |--------------------------------------------------------------------------
    |
    | Whether the sandbox simulation endpoint (/payment/{orderNumber}/simulate)
    | is active. Only enabled by default in local and testing environments, or
    | when HOYOS_DEMO_MODE is enabled.
    |
    */
    'allow_payment_simulation' => (bool) env(
        'HOYOS_ALLOW_PAYMENT_SIMULATION',
        env('HOYOS_DEMO_MODE', false) || in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)
    ),

    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | The default stock quantity threshold to consider a variant low stock.
    |
    */
    'low_stock_threshold' => (int) env('HOYOS_LOW_STOCK_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Default timezone for administrative time formatting and user display.
    |
    */
    'timezone' => env('HOYOS_TIMEZONE', 'Asia/Phnom_Penh'),

    /*
    |--------------------------------------------------------------------------
    | Report Timezone
    |--------------------------------------------------------------------------
    |
    | Timezone used for administrative reporting boundaries (today, this month).
    |
    */
    'report_timezone' => env('HOYOS_REPORT_TIMEZONE', 'Asia/Phnom_Penh'),
];

