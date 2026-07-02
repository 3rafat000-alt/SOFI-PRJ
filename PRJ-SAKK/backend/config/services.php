<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ccpayment' => [
        'app_id' => env('CCPAYMENT_APP_ID'),
        'app_secret' => env('CCPAYMENT_APP_SECRET'),
        'ip_whitelist' => env('CCPAYMENT_IP_WHITELIST', ''),
        'debug_mode' => env('CCPAYMENT_DEBUG_MODE', false),
        // Public base for the CCPayment callback URLs. Decoupled from APP_URL so
        // webhooks hit the live reachable host while branded URLs stay on sakk.app.
        'webhook_base' => env('CCPAYMENT_WEBHOOK_BASE', 'https://sakk.zanjour.com'),
        // Stuck-withdrawal sweeper (ReconcilePendingWithdrawals): age threshold
        // before a Phase-A-committed / Phase-B-never-ran withdrawal is swept.
        'reconcile_withdrawals_after_minutes' => env('CCPAYMENT_RECONCILE_WITHDRAWALS_AFTER_MINUTES', 10),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'issuing_webhook_secret' => env('STRIPE_ISSUING_WEBHOOK_SECRET'),
        'test_mode' => env('STRIPE_TEST_MODE', true),
    ],

    // Self-hosted OpenWA WhatsApp gateway — OTP / phone-verification delivery.
    // See app/Services/WhatsAppService.php. Disabled unless WHATSAPP_OTP_ENABLED=true.
    'whatsapp' => [
        'enabled' => env('WHATSAPP_OTP_ENABLED', false),
        'base_url' => env('OPENWA_BASE_URL', 'http://127.0.0.1:2785'),
        'api_key' => env('OPENWA_API_KEY'),
        'session_id' => env('OPENWA_SESSION_ID'),
        'default_country' => env('WHATSAPP_DEFAULT_COUNTRY', '963'),
        'timeout' => env('OPENWA_TIMEOUT', 15),
    ],

    // Telegram Bot API — second OTP delivery channel (account-linked).
    // See app/Services/TelegramService.php. Disabled unless TELEGRAM_OTP_ENABLED=true.
    'telegram' => [
        'enabled' => env('TELEGRAM_OTP_ENABLED', false),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'api_base' => env('TELEGRAM_API_BASE', 'https://api.telegram.org'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'timeout' => env('TELEGRAM_TIMEOUT', 15),
    ],

    // Telegram Gateway API — auto-detect OTP delivery by phone (no linking).
    // https://core.telegram.org/gateway/api · token from gateway.telegram.org (PAID).
    'telegram_gateway' => [
        'enabled' => env('TELEGRAM_GATEWAY_ENABLED', false),
        'token' => env('TELEGRAM_GATEWAY_TOKEN'),
        'ttl' => env('TELEGRAM_GATEWAY_TTL', 600),
        'timeout' => env('TELEGRAM_GATEWAY_TIMEOUT', 15),
    ],

    // Telegram support bot (@SakkSupportBot) — two-way bridge to the ticket desk.
    // SEPARATE token from the OTP bot. See app/Services/TelegramSupportService.php.
    'telegram_support' => [
        'enabled' => env('TELEGRAM_SUPPORT_ENABLED', false),
        'bot_token' => env('TELEGRAM_SUPPORT_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_SUPPORT_BOT_USERNAME'),
        'api_base' => env('TELEGRAM_API_BASE', 'https://api.telegram.org'),
        'webhook_secret' => env('TELEGRAM_SUPPORT_WEBHOOK_SECRET'),
        'timeout' => env('TELEGRAM_TIMEOUT', 15),
        // Public site the bot's menu links to (app download / website). Kept off
        // the possibly-stale APP_URL so deep links always hit the live host.
        'public_url' => env('TELEGRAM_PUBLIC_URL', 'https://sakk.zanjour.com'),
    ],

    // SMS OTP — final fallback channel (provider-agnostic HTTP gateway). "soon".
    'sms' => [
        'enabled' => env('SMS_OTP_ENABLED', false),
        'endpoint' => env('SMS_ENDPOINT'),
        'token' => env('SMS_TOKEN'),
        'sender' => env('SMS_SENDER', 'SAKK'),
        'timeout' => env('SMS_TIMEOUT', 15),
    ],

    'sham_cash' => [
        'base_url' => env('SHAMCASH_BASE_URL', 'https://api.shamcash.com'),
        'api_key' => env('SHAMCASH_API_KEY'),
        'api_secret' => env('SHAMCASH_API_SECRET'),
        'wallet_address' => env('SHAMCASH_WALLET_ADDRESS'),
        'webhook_secret' => env('SHAMCASH_WEBHOOK_SECRET'),
        'simulate' => env('SHAMCASH_SIMULATE', true),
        'min_deposit' => env('SHAMCASH_MIN_DEPOSIT', 10),
        'max_deposit' => env('SHAMCASH_MAX_DEPOSIT', 10000),
        'min_withdrawal' => env('SHAMCASH_MIN_WITHDRAWAL', 10),
        'max_withdrawal' => env('SHAMCASH_MAX_WITHDRAWAL', 5000),
    ],

    // Global gold spot price feed — drives automatic per-karat pricing.
    // See app/Services/GoldPriceService.php + `php artisan gold:update-prices`.
    // provider 'gold-api' (gold-api.com) is free and needs NO key (default).
    // provider 'goldapi'  (goldapi.io)   needs GOLDAPI_KEY.
    'gold' => [
        'provider' => env('GOLD_PRICE_PROVIDER', 'gold-api'),
        'goldapi_key' => env('GOLDAPI_KEY'),
        'timeout' => env('GOLD_PRICE_TIMEOUT', 15),
    ],

    // Google Maps — admin agent location picker (Maps JavaScript API + Places).
    // Browser-exposed key: restrict by HTTP referrer in Google Cloud Console.
    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'service_account_file' => env('FCM_SERVICE_ACCOUNT_FILE'),
    ],

    // ============================================================
    // External App Token — M2M authentication for trusted services.
    //
    // ExternalAppToken middleware accepts this token on payment-
    // request endpoints. The token is set in SAKK_APP_TOKEN env var
    // and must match what the external service sends in its
    // Authorization: Bearer header.
    //
    // The service user is looked up by email (service_user_email).
    // ============================================================
    'app_token' => env('SAKK_APP_TOKEN'),
    'service_user_email' => env('SAKK_SERVICE_USER_EMAIL', 'tasksync@sakk.com'),

];
