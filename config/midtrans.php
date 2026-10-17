<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    /*
    | HTTP client settings for calls to the Snap and Core (status) APIs.
    */
    'timeout' => (int) env('MIDTRANS_TIMEOUT', 10),
    'connect_timeout' => (int) env('MIDTRANS_CONNECT_TIMEOUT', 5),

    /*
    | Confirm webhook notifications with the status API before changing a donation.
    | The signature does not cover transaction_status/fraud_status, so this is on by default.
    */
    'verify_webhook_status' => (bool) env('MIDTRANS_VERIFY_WEBHOOK_STATUS', true),

    /*
    | Optional source-IP allowlist for the payment notification webhook.
    |
    | The SHA-512 signature check is the primary control and is always enforced.
    | Midtrans does not guarantee fixed notification IPs, so the allowlist is off
    | by default; enable it only with a list confirmed by Midtrans support.
    | MIDTRANS_WEBHOOK_IPS is a comma-separated list of IPs/CIDR ranges.
    */
    'ip_whitelist_enabled' => (bool) env('MIDTRANS_WEBHOOK_IP_WHITELIST', false),
    'ip_whitelist' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'MIDTRANS_WEBHOOK_IPS',
        '103.208.23.0/24,103.127.16.0/24,103.127.17.0/24'
    ))))),

    /*
    | Pending donations older than this are reconciled against the status API,
    | and expired when Midtrans has no record of them after the expiry window.
    */
    'reconcile_after_minutes' => (int) env('MIDTRANS_RECONCILE_AFTER_MINUTES', 15),
    'expire_after_hours' => (int) env('MIDTRANS_EXPIRE_AFTER_HOURS', 24),
];
