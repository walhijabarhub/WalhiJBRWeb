<?php

return [
    /*
    | Personal data retention (UU No. 27/2022 tentang Pelindungan Data Pribadi).
    | Applied monthly by `php artisan walhi:prune-personal-data`.
    */

    // Donor and commenter contact details are anonymised after this many months.
    'retention_months' => (int) env('PRIVACY_RETENTION_MONTHS', 24),

    // Newsletter sign-ups that were never confirmed are deleted after this many days.
    'unconfirmed_subscriber_days' => (int) env('PRIVACY_UNCONFIRMED_SUBSCRIBER_DAYS', 30),

    // Soft-deleted subscribers are permanently removed after this many days.
    'deleted_subscriber_days' => (int) env('PRIVACY_DELETED_SUBSCRIBER_DAYS', 30),
];
