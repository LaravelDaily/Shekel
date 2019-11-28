<?php

return [

    'billable_model' => env('BILLABLE_MODEL', 'App\\User'),

    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC'),
        'secret_key' => env('STRIPE_SECRET'),
    ],
];


?>