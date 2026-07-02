<?php

/**
 * KYC configuration — SINGLE SOURCE OF TRUTH for the 3-level system.
 *
 * Both the seeder (KycLevelSeeder) and the runtime service (KycService) read
 * from here, so level definitions and limits can never diverge again.
 *
 * Levels:
 *   0 → غير موثّق (Unverified)   — default on signup, very low limits
 *   1 → موثّق أساسي (Standard)   — email + phone + id_document
 *   2 → موثّق كامل (Verified)    — email + phone + id_document + selfie
 *
 * Limits are dual-currency (USD + SYP). SYP ≈ USD × 13,000.
 * Plus: balance_limit (max wallet balance) and cards_limit (max cards).
 * Documents are created with PENDING status and require explicit admin review
 * before upgrading the user's KYC level. An admin may approve or reject;
 * rejection downgrades the user to the highest level still met.
 */

return [

    // SYP-per-USD factor used when deriving SYP limits from USD.
    'syp_factor' => 13000,

    // All verification types that exist in the system.
    'verification_types' => ['email', 'phone', 'id_document', 'selfie', 'address_proof'],

    'levels' => [
        [
            'level' => 0,
            'key' => 'unverified',
            'name' => 'Unverified',
            'name_ar' => 'غير موثّق',
            'description' => 'Account created — very limited until verified.',
            'description_ar' => 'تم إنشاء الحساب — حدود منخفضة جداً حتى يتم التوثيق.',
            'requirements' => [],
            'limits' => [
                'USD' => ['daily' => 100, 'monthly' => 300, 'single' => 100],
                'SYP' => ['daily' => 1300000, 'monthly' => 3900000, 'single' => 1300000],
            ],
            'balance_limit' => [
                'USD' => 500,
                'SYP' => 6500000,
            ],
            'cards_limit' => 0,
            'can_transfer' => true,
            'can_withdraw' => false,
            'can_create_card' => false,
        ],
        [
            'level' => 1,
            'key' => 'standard',
            'name' => 'Standard KYC',
            'name_ar' => 'موثّق أساسي',
            'description' => 'Basic identity verified — moderate limits.',
            'description_ar' => 'تم توثيق الهوية الأساسية — حدود متوسطة.',
            'requirements' => ['email', 'phone', 'id_document'],
            'limits' => [
                'USD' => ['daily' => 2500, 'monthly' => 10000, 'single' => 500],
                'SYP' => ['daily' => 32500000, 'monthly' => 130000000, 'single' => 6500000],
            ],
            'balance_limit' => [
                'USD' => 5000,
                'SYP' => 65000000,
            ],
            'cards_limit' => 3,
            'can_transfer' => true,
            'can_withdraw' => true,
            'can_create_card' => true,
        ],
        [
            'level' => 2,
            'key' => 'verified',
            'name' => 'Fully Verified',
            'name_ar' => 'موثّق كامل',
            'description' => 'Full identity verified — highest limits and full access.',
            'description_ar' => 'تم توثيق الهوية بالكامل — أعلى الحدود وكل الميزات.',
            'requirements' => ['email', 'phone', 'id_document', 'selfie'],
            'limits' => [
                'USD' => ['daily' => 10000, 'monthly' => 50000, 'single' => 5000],
                'SYP' => ['daily' => 130000000, 'monthly' => 650000000, 'single' => 65000000],
            ],
            'balance_limit' => [
                'USD' => 50000,
                'SYP' => 650000000,
            ],
            'cards_limit' => 10,
            'can_transfer' => true,
            'can_withdraw' => true,
            'can_create_card' => true,
        ],
    ],

    // Document types accepted for the id_document step.
    'id_document_types' => ['national_id', 'passport', 'drivers_license'],

    // OTP settings.
    'email_code_ttl_minutes' => 15,
    'phone_code_ttl_minutes' => 10,

    // SEC H8: max wrong OTP guesses before the code is burned and a new one is
    // required (anti brute-force; the verify routes are also throttle:otp).
    'max_otp_attempts' => 5,
];
