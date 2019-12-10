<?php

use Shekel\Providers\StripePaymentProvider;

return [

    'providers' => [
        StripePaymentProvider::class,
    ],

    'billable_model' => env('BILLABLE_MODEL', 'App\\User'),
    'billable_currency' => env('BILLABLE_CURRENCY', 'usd'),

    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC'),
        'secret_key' => env('STRIPE_SECRET'),
    ],
];


?>