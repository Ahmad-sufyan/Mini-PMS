<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clinic Details
    |--------------------------------------------------------------------------
    |
    | These values describe the clinic running this installation. They are
    | shown across the application (dashboard, invoices, etc.) and can be
    | customised per installation through the environment file.
    |
    */

    'name' => env('CLINIC_NAME', 'Mini PMS Clinic'),

    'address' => env('CLINIC_ADDRESS', '123 Health Street, Springfield'),

    'phone' => env('CLINIC_PHONE', '+1 555 0100'),

    /*
    |--------------------------------------------------------------------------
    | Clinic Timezone
    |--------------------------------------------------------------------------
    |
    | The timezone the clinic operates in. Dates are stored in UTC and are
    | converted to this timezone when scheduling and displaying them.
    |
    */

    'timezone' => env('CLINIC_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Opening Hours & Working Days
    |--------------------------------------------------------------------------
    |
    | The daily opening hours (24-hour "H:i" format) and the days of the week
    | the clinic is open, as lowercase three-letter day names (mon-sun).
    |
    */

    'opening_hours' => [
        'opens_at' => env('CLINIC_OPENS_AT', '09:00'),
        'closes_at' => env('CLINIC_CLOSES_AT', '17:00'),
    ],

    'working_days' => array_values(array_filter(array_map(
        fn (string $day): string => strtolower(trim($day)),
        explode(',', (string) env('CLINIC_WORKING_DAYS', 'mon,tue,wed,thu,fri,sat')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The ISO 4217 currency code used for billing and invoices.
    |
    */

    'currency' => env('CLINIC_CURRENCY', 'USD'),

];
