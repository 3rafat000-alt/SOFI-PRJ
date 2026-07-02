<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AgentController;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\QrAuthController;
use App\Http\Middleware\EnsureDeviceCanTransact;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\API\CardController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\CashbackController;
use App\Http\Controllers\API\KycController;
use App\Http\Controllers\API\ExchangeRateController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\BiometricController;
use App\Http\Controllers\API\GoldSavingsController;
use App\Http\Controllers\API\SavingsController;
use App\Http\Controllers\API\PartnerApplicationController;
use App\Http\Controllers\API\CompanyApplicationController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\AdminUserController;
use App\Http\Controllers\API\CCPaymentController;
use App\Http\Controllers\API\SupportTicketController;
use App\Http\Controllers\API\PaymentRequestController;
use App\Http\Controllers\API\AppController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\TelegramController;
use App\Http\Controllers\API\TelegramSupportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SAKK Wallet API Routes
|--------------------------------------------------------------------------
|
| Version: 1.0
| Base URL: /api/v1
|
*/

// ============================================
// Public Routes (No Authentication Required)
// ============================================

Route::prefix('v1')->middleware('throttle:api')->group(function () {

    // Authentication (strict rate limiting: 5 per minute)
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);

        // QR Auth
        Route::post('/qr/generate', [QrAuthController::class, 'generate']);
        Route::get('/qr/poll/{token}', [QrAuthController::class, 'poll']);
    });

    // Feature flags — clients read this at boot to show/hide gated features
    // (e.g. the cards tab stays hidden until Stripe Issuing is configured).
    Route::get('/features', fn () => response()->json([
        'success' => true,
        'data' => [
            'cards_enabled' => \App\Support\CardsFeature::enabled(),
        ],
    ]));

    // App version / force-update policy — clients read this at boot and show
    // a blocking "Update Required" screen when their build is below the floor
    // set by the admin panel (group `app_update` in system_settings).
    Route::get('/app/version', [AppController::class, 'version']);

    // Technical-support contacts — clients render these in the "تواصل معنا" screen.
    Route::get('/app/support', [AppController::class, 'support']);

    // Static Pages
    Route::get('/privacy', [AuthController::class, 'privacyPolicy']);
    Route::get('/terms', [AuthController::class, 'termsOfService']);

    // Transaction Types & Categories (public reference)
    Route::get('/transactions/types', [TransactionController::class, 'types']);
    Route::get('/transactions/categories', [TransactionController::class, 'categories']);

    // Telegram inbound webhook — Telegram pushes `/start <token>` here to link a
    // chat to an account. Public (no auth); guarded by a secret-token header.
    Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

    // Telegram SUPPORT bot inbound webhook — a user's message becomes a support
    // ticket message. Public; guarded by its own secret-token header.
    Route::post('/telegram/support/webhook', [TelegramSupportController::class, 'webhook']);

    // ============================================
    // Protected Routes (Authentication Required)
    // ============================================

    Route::middleware('auth:sanctum')->group(function () {

        // Auth Management
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::put('/password', [AuthController::class, 'changePassword']);
            Route::post('/pin', [AuthController::class, 'setPin']);
            Route::post('/pin/verify', [AuthController::class, 'verifyPin']);
            // 2FA
            Route::post('/2fa/setup', [AuthController::class, 'twoFactorSetup']);
            Route::post('/2fa/confirm', [AuthController::class, 'twoFactorConfirm']);
            Route::post('/2fa/disable', [AuthController::class, 'twoFactorDisable']);
            Route::get('/2fa/status', [AuthController::class, 'twoFactorStatus']);
            Route::post('/2fa/recovery-codes', [AuthController::class, 'twoFactorRecoveryCodes']);

            // QR Auth Approve (from mobile app)
            Route::post('/qr/approve', [QrAuthController::class, 'approve']);

            // Biometric Authentication
            Route::prefix('biometric')->group(function () {
                Route::post('/devices', [BiometricController::class, 'registerDevice']);
                Route::get('/devices', [BiometricController::class, 'getDevices']);
                Route::delete('/devices/{id}', [BiometricController::class, 'removeDevice']);
                Route::post('/challenge', [BiometricController::class, 'challenge']);
                Route::post('/verify', [BiometricController::class, 'verify']);
            });

            // Change PIN
            Route::post('/pin/change', [AuthController::class, 'changePin']);
            // Disable PIN (requires password)
            Route::post('/pin/disable', [AuthController::class, 'disablePin']);
        });

        // ==========================================
        // Live Chat (customer support, polling transport)
        // ==========================================
        Route::prefix('chat')->group(function () {
            Route::get('/conversation', [ChatController::class, 'conversation']);
            Route::get('/messages', [ChatController::class, 'messages']);
            Route::post('/messages', [ChatController::class, 'send'])->middleware('throttle:30,1');
        });

        // ==========================================
        // Connected Devices (new-device approval + 48h transaction hold)
        // ==========================================
        Route::prefix('devices')->group(function () {
            Route::get('/', [DeviceController::class, 'index']);
            Route::post('/register', [DeviceController::class, 'register']);
            Route::post('/{id}/approve', [DeviceController::class, 'approve']);
            Route::post('/{id}/reject', [DeviceController::class, 'reject']);
            Route::delete('/{id}', [DeviceController::class, 'remove']);
        });

        // ==========================================
        // Wallets
        // ==========================================
        Route::prefix('wallets')->group(function () {
            Route::get('/', [WalletController::class, 'index']);
            Route::post('/', [WalletController::class, 'store']);
            Route::post('/convert', [WalletController::class, 'convert']);
            Route::get('/exchange-rates', [ExchangeRateController::class, 'getAllRates']);

            Route::get('/{wallet}', [WalletController::class, 'show']);
            Route::get('/{wallet}/balance', [WalletController::class, 'balance']);
            Route::get('/{wallet}/transactions', [WalletController::class, 'transactions']);
            Route::get('/{wallet}/stats', [WalletController::class, 'stats']);
            Route::get('/{wallet}/deposit-address', [WalletController::class, 'depositAddress']);
            Route::delete('/{wallet}', [WalletController::class, 'destroy']);

            // Wallet Operations
            // SEC C1: the self-serve "deposit" SIMULATES a credit with no payment
            // provider — reachable in production it lets any authenticated user mint
            // unlimited balance. Real deposits flow ONLY through the signed CCPayment
            // webhook (see /ccpayment/deposit/address). Registered for local/test only;
            // the controller also hard-guards on environment (defense in depth).
            if (app()->environment('local', 'testing')) {
                Route::post('/{wallet}/deposit', [WalletController::class, 'deposit']);
            }
            // Item 5 (desk review): X-Idempotency-Key guard, fail-open mode —
            // dedupes a double-tap/retry on weak mobile networks without
            // breaking clients that don't send the header yet.
            Route::post('/{wallet}/withdraw', [WalletController::class, 'withdraw'])
                ->middleware(['idempotency', EnsureDeviceCanTransact::class])
                ->name('api.wallet.withdraw');
        });

        // ==========================================
        // P2P Transfer (send money to another SAKK user)
        // ==========================================
        Route::prefix('transfer')->group(function () {
            Route::get('/lookup', [\App\Http\Controllers\API\TransferController::class, 'lookup']);
            // Item 5 (desk review): X-Idempotency-Key guard, fail-open mode.
            Route::post('/', [\App\Http\Controllers\API\TransferController::class, 'transfer'])
                ->middleware(['idempotency', EnsureDeviceCanTransact::class])
                ->name('api.transfer.create');
        });

        // ==========================================
        // Contacts matching + Referral
        // ==========================================
        Route::post('/contacts/match', [\App\Http\Controllers\API\ContactController::class, 'match']);
        Route::get('/referral/info', [\App\Http\Controllers\API\ReferralController::class, 'info']);

        // ==========================================
        // Virtual Cards — gated: disabled until Stripe Issuing is configured
        // (admin → النظام → الطرف الثالث → ستريب). Turns on with no code change.
        // ==========================================
        Route::prefix('cards')->middleware('cards.enabled')->group(function () {
            Route::get('/', [CardController::class, 'index']);
            Route::post('/', [CardController::class, 'store']);
            Route::get('/{card}', [CardController::class, 'show']);
            Route::put('/{card}', [CardController::class, 'update']);
            Route::get('/{card}/transactions', [CardController::class, 'transactions']);

            // Card Details (sensitive - requires PIN)
            Route::post('/{card}/details', [CardController::class, 'details']);

            // Card Operations
            Route::post('/{card}/load', [CardController::class, 'load'])
                ->middleware(EnsureDeviceCanTransact::class);
            Route::post('/{card}/unload', [CardController::class, 'unload'])
                ->middleware(EnsureDeviceCanTransact::class);
            Route::post('/{card}/freeze', [CardController::class, 'freeze']);
            Route::post('/{card}/unfreeze', [CardController::class, 'unfreeze']);
            Route::post('/{card}/cancel', [CardController::class, 'cancel']);
            
            // Stripe Issuing Cards
            Route::post('/stripe/issue', [CardController::class, 'issueStripeCard']);
            Route::post('/{card}/stripe/details', [CardController::class, 'stripeCardDetails']);
        });

        // ==========================================
        // Transactions
        // ==========================================
        Route::prefix('transactions')->group(function () {
            Route::get('/', [TransactionController::class, 'index']);
            Route::get('/stats', [TransactionController::class, 'stats']);
            Route::get('/export', [TransactionController::class, 'export']);
            Route::get('/reference/{reference}', [TransactionController::class, 'byReference']);
            Route::get('/{transaction}', [TransactionController::class, 'show']);
        });

        // Cashback (earned rewards) — summary + history.
        Route::get('/cashback', [CashbackController::class, 'index']);

        // ==========================================
        // Gold Savings (buy/sell grams at real gold prices)
        // ==========================================
        Route::prefix('gold')->group(function () {
            Route::get('/prices', [GoldSavingsController::class, 'prices']);
            Route::get('/wallet', [GoldSavingsController::class, 'wallet']);
            // SEC parity: gold buy/sell move funds → same new-device guard as wallet/transfer.
            Route::post('/buy', [GoldSavingsController::class, 'buy'])
                ->middleware(EnsureDeviceCanTransact::class);
            Route::post('/sell', [GoldSavingsController::class, 'sell'])
                ->middleware(EnsureDeviceCanTransact::class);
            Route::get('/transactions', [GoldSavingsController::class, 'transactions']);
            Route::get('/stats', [GoldSavingsController::class, 'stats']);
        });

        // ==========================================
        // Partner Application (join as agent or merchant)
        // ==========================================
        Route::prefix('partner')->group(function () {
            Route::get('/application', [PartnerApplicationController::class, 'application']);
            Route::post('/apply', [PartnerApplicationController::class, 'apply']);
            Route::post('/documents', [PartnerApplicationController::class, 'uploadDocument']);
        });

        // ==========================================
        // Company Application (انضم كشركة — register a company for payroll)
        // ==========================================
        Route::prefix('company')->group(function () {
            Route::get('/application', [CompanyApplicationController::class, 'application']);
            Route::post('/apply', [CompanyApplicationController::class, 'apply']);
            Route::post('/documents', [CompanyApplicationController::class, 'uploadDocument']);
        });

        // ==========================================
        // Savings (cash-savings goals — separate from gold)
        // ==========================================
        Route::prefix('savings')->group(function () {
            Route::get('/summary', [SavingsController::class, 'summary']);
            Route::get('/', [SavingsController::class, 'index']);
            Route::post('/', [SavingsController::class, 'store']);
            Route::get('/{savings}', [SavingsController::class, 'show']);
            Route::post('/{savings}/deposit', [SavingsController::class, 'deposit'])
                ->middleware(EnsureDeviceCanTransact::class);
            Route::post('/{savings}/withdraw', [SavingsController::class, 'withdraw'])
                ->middleware(EnsureDeviceCanTransact::class);
            Route::post('/{savings}/close', [SavingsController::class, 'close']);
        });

        // ==========================================
        // User Profile
        // ==========================================
        Route::prefix('profile')->group(function () {
            Route::get('/', [AuthController::class, 'me']);
            Route::put('/', [AuthController::class, 'updateProfile']);
            // SEC M2: block active-content uploads (svg/html/php) at the edge.
            Route::post('/avatar', [AuthController::class, 'updateAvatar'])
                ->middleware('block-dangerous-uploads');
            Route::delete('/avatar', [AuthController::class, 'deleteAvatar']);
            Route::delete('/', [AuthController::class, 'deleteAccount']);
        });

        // ==========================================
        // Telegram OTP channel — account linking
        // ==========================================
        Route::prefix('telegram')->group(function () {
            Route::get('/link', [TelegramController::class, 'link']);
            Route::get('/status', [TelegramController::class, 'status']);
            Route::post('/unlink', [TelegramController::class, 'unlink']);
        });

        // ==========================================
        // KYC (Know Your Customer) - 3 Level System
        // ==========================================
        Route::prefix('kyc')->group(function () {
            Route::get('/levels', [KycController::class, 'getLevels']);
            Route::get('/status', [KycController::class, 'getStatus']);
            Route::get('/submissions', [KycController::class, 'getSubmissionStatus']);
            
            // Email Verification (Level 1) — OTP dispatch strictly rate limited (anti-flood)
            Route::post('/email/send', [KycController::class, 'sendEmailCode'])
                ->middleware('throttle:otp');
            // SEC H8: verify is also throttled — without it a 6-digit code is brute-forceable.
            Route::post('/email/verify', [KycController::class, 'verifyEmailCode'])
                ->middleware('throttle:otp');

            // Phone Verification (Level 2) — OTP dispatch strictly rate limited (anti-flood)
            Route::post('/phone/update', [KycController::class, 'updatePhone']);
            Route::post('/phone/send', [KycController::class, 'sendPhoneCode'])
                ->middleware('throttle:otp');
            Route::post('/phone/verify', [KycController::class, 'verifyPhoneCode'])
                ->middleware('throttle:otp');

            // Document Verification (Level 3) — block active-content uploads (svg/html/php) at the edge
            Route::post('/id-document', [KycController::class, 'submitIdDocument'])
                ->middleware('block-dangerous-uploads');
            Route::post('/selfie', [KycController::class, 'submitSelfie'])
                ->middleware('block-dangerous-uploads');
            Route::post('/address-proof', [KycController::class, 'submitAddressProof'])
                ->middleware('block-dangerous-uploads');
        });

        // ==========================================
        // CCPayment Integration (Crypto)
        // ==========================================
        Route::prefix('ccpayment')->group(function () {
            // Configuration & Info
            Route::get('/config', [CCPaymentController::class, 'getConfig']);
            Route::get('/supported-coins', [CCPaymentController::class, 'getSupportedCoins']);
            
            // Deposit
            Route::post('/deposit/address', [CCPaymentController::class, 'createDepositAddress']);
            Route::get('/deposit/{reference}/status', [CCPaymentController::class, 'getDepositStatus']);
            Route::get('/deposits', [CCPaymentController::class, 'getDepositHistory']);
            
            // Withdraw
            // W-SEV-5: idempotency-key guard sits BEFORE the device gate so a
            // duplicate/replayed request short-circuits before any device
            // policy check, matching the "no money code re-runs" contract.
            Route::post('/withdraw', [CCPaymentController::class, 'withdraw'])
                ->middleware(['idempotency:required', EnsureDeviceCanTransact::class])
                ->name('api.ccpayment.withdraw');
            Route::get('/withdraw/fee', [CCPaymentController::class, 'getWithdrawFee']);
            Route::get('/withdraw/{reference}/status', [CCPaymentController::class, 'getWithdrawStatus']);
            Route::get('/withdrawals', [CCPaymentController::class, 'getWithdrawHistory']);
            
            // Assets
            Route::get('/assets', [CCPaymentController::class, 'getAssets']);
            Route::get('/assets/{coinId}', [CCPaymentController::class, 'getAssetDetail']);
        });

        // ==========================================
        // Support Tickets (Customer support desk)
        // ==========================================
        Route::prefix('support')->group(function () {
            Route::get('/categories', [SupportTicketController::class, 'categories']);
            Route::get('/tickets', [SupportTicketController::class, 'index']);
            Route::post('/tickets', [SupportTicketController::class, 'store'])->middleware('throttle:6,1');
            Route::get('/tickets/{uuid}', [SupportTicketController::class, 'show']);
            Route::post('/tickets/{uuid}/reply', [SupportTicketController::class, 'reply'])->middleware('throttle:20,1');
        });

        // ==========================================
        // Exchange Rates (Simplified: Single USD/SYP row)
        // ==========================================
        Route::prefix('exchange-rates')->group(function () {
            Route::get('/', [ExchangeRateController::class, 'getAllRates']);
            Route::get('/rate', [ExchangeRateController::class, 'getRate']);
            Route::post('/convert', [ExchangeRateController::class, 'convert']);
            Route::get('/history', [ExchangeRateController::class, 'getHistory']);
            Route::get('/configured', [ExchangeRateController::class, 'isConfigured']);
        });

        // ==========================================
        // Fees (Dynamic Fee Structure)
        // ==========================================
        Route::prefix('fees')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\FeeController::class, 'apiIndex']);
            Route::post('/calculate', [\App\Http\Controllers\Admin\FeeController::class, 'apiCalculate']);
        });

        // ==========================================
        // Cash Agents (nearby agent finder for cash in/out)
        // ==========================================
        Route::prefix('agents')->group(function () {
            Route::get('/', [AgentController::class, 'index']);
            Route::get('/{agent}', [AgentController::class, 'show']);
        });

        // ==========================================
        // Notifications
        // ==========================================
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/fcm-token', [NotificationController::class, 'updateFcmToken']);
        });

        // ==========================================
        // Admin Routes (requires is_admin = true)
        // ==========================================
        Route::prefix('admin')->middleware(['admin', 'throttle:admin'])->group(function () {
            // Dashboard
            Route::get('/dashboard', [AdminController::class, 'dashboard']);

            // Users (delegated to AdminUserController)
            Route::get('/users', [AdminUserController::class, 'index']);
            Route::get('/users/{id}', [AdminUserController::class, 'show']);
            Route::put('/users/{id}', [AdminUserController::class, 'update']);
            Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

            // KYC (delegated to AdminUserController)
            Route::get('/kyc-documents', [AdminUserController::class, 'kycDocuments']);
            Route::post('/kyc/{userId}/approve', [AdminUserController::class, 'approveKyc']);
            Route::post('/kyc/{userId}/reject', [AdminUserController::class, 'rejectKyc']);

            // Transactions
            Route::get('/transactions', [AdminController::class, 'transactions']);
            Route::get('/transactions/{id}', [AdminController::class, 'transactionDetail']);
            Route::post('/transactions/{id}/reverse', [AdminController::class, 'reverseTransaction']);

            // Wallets
            Route::get('/wallets', [AdminController::class, 'wallets']);
            Route::post('/wallets/{id}/freeze', [AdminController::class, 'freezeWallet']);
            Route::post('/wallets/{id}/unfreeze', [AdminController::class, 'unfreezeWallet']);

            // Cards (Full Control)
            Route::get('/cards', [AdminController::class, 'cards']);
            Route::post('/cards/{id}/freeze', [AdminController::class, 'freezeCard']);
            Route::post('/cards/{id}/unfreeze', [AdminController::class, 'unfreezeCard']);
            Route::post('/cards/{id}/cancel', [AdminController::class, 'cancelCard']);
            Route::put('/cards/{id}/limits', [AdminController::class, 'updateCardLimits']);

            // System Settings
            Route::get('/settings', [AdminController::class, 'systemSettings']);
            Route::get('/settings/all', [AdminController::class, 'getAllSettings']);
            Route::get('/settings/group/{group}', [AdminController::class, 'getSettingsByGroup']);
            Route::post('/settings', [AdminController::class, 'updateSetting']);
            Route::delete('/settings/{key}', [AdminController::class, 'deleteSetting']);

            // Fees & Limits
            Route::get('/fees', [AdminController::class, 'getFees']);
            Route::put('/fees', [AdminController::class, 'updateFees']);
            Route::get('/limits', [AdminController::class, 'getLimits']);
            Route::put('/limits', [AdminController::class, 'updateLimits']);

            // Activity Logs
            Route::get('/activity-logs', [AdminController::class, 'activityLogs']);

            // Push Notifications
            Route::get('/notifications', [AdminController::class, 'getNotifications']);
            Route::post('/notifications/send', [AdminController::class, 'sendNotification']);

            // Maintenance Mode
            Route::post('/maintenance/enable', [AdminController::class, 'enableMaintenance']);
            Route::post('/maintenance/disable', [AdminController::class, 'disableMaintenance']);

            // Currencies
            Route::get('/currencies', [AdminController::class, 'getCurrencies']);
            Route::put('/currencies', [AdminController::class, 'updateCurrencies']);

            // Referrals
            Route::get('/referrals', [AdminController::class, 'getReferrals']);
            Route::get('/referrals/stats', [AdminController::class, 'getReferralStats']);
            Route::put('/referrals/config', [AdminController::class, 'updateReferralConfig']);

            // Reports
            Route::get('/reports', [AdminController::class, 'getReports']);

            // Export
            Route::get('/export/{type}', [AdminController::class, 'exportCsv']);

            // Exchange Rates
            Route::get('/exchange-rates', [AdminController::class, 'getExchangeRates']);
            Route::post('/exchange-rates', [AdminController::class, 'updateExchangeRate']);

            // Advanced Fees
            Route::get('/fees/all', [AdminController::class, 'getAllFees']);
            Route::post('/fees/update', [AdminController::class, 'updateFee']);

            // KYC Levels
            Route::get('/kyc-levels', [AdminController::class, 'getKycLevels']);
            Route::post('/kyc-levels', [AdminController::class, 'updateKycLevel']);
            Route::get('/kyc-verifications', [AdminController::class, 'getPendingKycVerifications']);
            Route::post('/kyc-verifications/{id}/review', [AdminController::class, 'reviewKycVerification']);

            // Card Inventory
            Route::get('/card-inventory', [AdminController::class, 'getCardInventory']);
            Route::post('/card-inventory/import', [AdminController::class, 'importCards']);
            Route::get('/card-pricing', [AdminController::class, 'getCardPricing']);
            Route::post('/card-pricing', [AdminController::class, 'updateCardPricing']);
    });
});

        // ==========================================
        // Payment Requests (request money via link/QR)
        //
        // OUTSIDE auth:sanctum — uses dual middleware: app-token (M2M) OR
        // auth:sanctum (mobile app users). app-token passes through if no
        // matching SAKK_APP_TOKEN is found, so auth:sanctum handles normal
        // Sanctum auth. Order in the middleware array matters: app-token first.
        // ==========================================
        Route::prefix('payment-requests')->middleware('app-token')->group(function () {
            Route::get('/', [PaymentRequestController::class, 'index']);
            Route::get('/received', [PaymentRequestController::class, 'received']);
            Route::post('/', [PaymentRequestController::class, 'store']);
            Route::get('/{paymentRequest}', [PaymentRequestController::class, 'show']);
            Route::post('/{paymentRequest}/pay', [PaymentRequestController::class, 'pay'])
                ->middleware(EnsureDeviceCanTransact::class);
            // SEC H2: accept() moves money identically to pay() — it must carry the
            // same new-device transaction guard.
            Route::post('/{paymentRequest}/accept', [PaymentRequestController::class, 'accept'])
                ->middleware(EnsureDeviceCanTransact::class);
            Route::post('/{paymentRequest}/reject', [PaymentRequestController::class, 'reject']);
            Route::post('/{paymentRequest}/cancel', [PaymentRequestController::class, 'cancel']);
        });
});

/*
|--------------------------------------------------------------------------
| API Documentation
|--------------------------------------------------------------------------
|
| Endpoint: GET /api/docs
| Returns: OpenAPI 3.0 specification
|
*/
Route::get('/docs', function () {
    return response()->file(base_path('docs/openapi.yaml'));
});

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

/*
|--------------------------------------------------------------------------
| Webhooks (No Auth Required)
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->group(function () {
    // Stripe Issuing Webhooks (Critical: 2-second timeout for authorization.request)
    Route::post('/stripe/issuing', [\App\Http\Controllers\Webhooks\StripeIssuingWebhookController::class, 'handle'])
        ->name('api.webhooks.stripe.issuing');
});

/*
|--------------------------------------------------------------------------
| CCPayment Webhook Test Endpoints (Development Only)
|--------------------------------------------------------------------------
| SEC M4: these credit/debit wallets with attacker-chosen amounts. They are
| registered ONLY in local/testing so they cannot exist in a production route
| table at all (the previous in-handler env check was the sole protection).
*/
if (app()->environment('local', 'testing') && config('app.test_mode_enabled')) {
    Route::prefix('webhooks/ccpayment')->group(function () {
        Route::get('/info', [\App\Http\Controllers\Webhooks\CCPaymentWebhookController::class, 'info'])->name('api.webhooks.ccpayment.info');
        Route::post('/test/deposit', [\App\Http\Controllers\Webhooks\CCPaymentWebhookController::class, 'testDeposit'])->name('api.webhooks.ccpayment.test.deposit');
        Route::post('/test/withdraw', [\App\Http\Controllers\Webhooks\CCPaymentWebhookController::class, 'testWithdraw'])->name('api.webhooks.ccpayment.test.withdraw');
    });
}


