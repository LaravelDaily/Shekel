<?php

return [
    'vendors' => ['stripe',],

    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC'),
        'secret_key' => env('STRIPE_SECRET'),
    ],
];


?>