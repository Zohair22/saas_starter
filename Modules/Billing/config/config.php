<?php

return [
    'name' => 'Billing',
    'plans' => [
        'free' => [
            'name' => 'Free',
            'stripe_price_id' => env('STRIPE_PRICE_FREE'),
            'max_users' => 3,
            'max_projects' => 5,
            'api_rate_limit' => 1000,
        ],
        'pro' => [
            'name' => 'Pro',
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'max_users' => 25,
            'max_projects' => 100,
            'api_rate_limit' => 15000,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
            'max_users' => 1000,
            'max_projects' => 10000,
            'api_rate_limit' => 200000,
        ],
    ],
];
