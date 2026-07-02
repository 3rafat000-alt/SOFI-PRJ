<?php

use App\Models\Integration;

function enableCardsFeature(): void
{
    Integration::create([
        'key' => 'stripe',
        'name' => 'Stripe',
        'name_ar' => 'سترايب',
        'description' => 'Stripe payment gateway',
        'is_active' => true,
        'credentials' => ['secret' => 'sk_test_mocked'],
    ]);
}
