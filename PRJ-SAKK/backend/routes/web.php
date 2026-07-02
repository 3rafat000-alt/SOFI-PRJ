<?php

use Illuminate\Support\Facades\Route;
use App\Services\ExchangeRateService;
use App\Models\GoldPrice;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\TwoFactorProfileController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\AgentController as AdminAgentController;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\GoldPriceController;
use App\Http\Controllers\Admin\AgentDocumentController;
use App\Http\Controllers\Admin\MerchantDocumentController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\AppUpdateController;
use App\Http\Controllers\Admin\LiveChatController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\SecureFileController;
use App\Http\Controllers\Admin\PushNotificationController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\DatabaseBackupController;

// Landing Page
Route::get('/', fn () => view('landing'))->name('landing');

/*


|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Installer Routes
Route::prefix('install')->name('installer.')->middleware('installer')->group(function () {
    Route::get('/', [InstallerController::class, 'requirements'])->name('requirements');
    Route::get('/database', [InstallerController::class, 'database'])->name('database');
    Route::post('/database', [InstallerController::class, 'databaseStore'])->name('database.store');
    Route::get('/admin', [InstallerController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallerController::class, 'adminStore'])->name('admin.store');
    Route::get('/settings', [InstallerController::class, 'settings'])->name('settings');
    Route::post('/settings', [InstallerController::class, 'settingsStore'])->name('settings.store');
    Route::get('/complete', [InstallerController::class, 'complete'])->name('complete');
});

// Admin Auth Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:admin-login')->name('login.submit');
    Route::get('/login/2fa', [AuthController::class, 'showTwoFactor'])->name('login.2fa');
    Route::post('/login/2fa', [AuthController::class, 'verifyTwoFactor'])->middleware('throttle:admin-2fa')->name('login.2fa.verify');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Admin Panel Routes (Protected)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Secure document streaming — single authorised egress for KYC / partner
    // identity PII on the private disk. Link: route('admin.secure-file', ['path' => encrypt($rel)]).
    Route::get('/secure-file', [SecureFileController::class, 'show'])->name('secure-file');

    // Users — static routes BEFORE {user} wildcard to avoid routing collision
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/kpis', [UserController::class, 'kpis'])->name('users.kpis');
    Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
    Route::post('/users/bulk', [UserController::class, 'bulk'])->name('users.bulk');

    // Users — {user} wildcard routes (VIEW-ONLY + status/KYC-doc review only)
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/quick-view', [UserController::class, 'quickView'])->name('users.quick-view');
    Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::post('/users/{user}/update-status', [UserController::class, 'updateStatus'])->name('users.update-status');
    Route::post('/users/{user}/kyc/{doc}/approve', [UserController::class, 'approveKycDoc'])->name('users.kyc.approve');
    Route::post('/users/{user}/kyc/{doc}/reject', [UserController::class, 'rejectKycDoc'])->name('users.kyc.reject');
    
    // Transactions — static routes BEFORE {transaction} wildcard to avoid collision
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');
    Route::get('/transactions/kpis', [TransactionController::class, 'kpis'])->name('transactions.kpis');
    Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');

    // Transactions — {transaction} wildcard routes (READ-ONLY + audited reverse only)
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::get('/transactions/{transaction}/quick-view', [TransactionController::class, 'quickView'])->name('transactions.quick-view');
    Route::get('/transactions/{transaction}/invoice', [TransactionController::class, 'invoice'])->name('transactions.invoice');
    Route::post('/transactions/{transaction}/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');

    // Cards & KYC have no standalone admin pages — managed inside the user page
    // (cards tab + KYC tab with approve/reject via admin.users.kyc.*).

    // Exchange Rates (standalone beautiful page)
    Route::prefix('exchange-rates')->name('exchange-rates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ExchangeRateController::class, 'index'])->name('index');
        Route::put('/', [\App\Http\Controllers\Admin\ExchangeRateController::class, 'update'])->name('update');
    });

    // Settings (system settings only — profile is a standalone page)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/setting', [SettingsController::class, 'updateSetting'])->name('settings.setting.update');
    // Fee routes moved to standalone FeeController
    Route::post('/settings/cache/clear', [SettingsController::class, 'clearCache'])->name('settings.cache.clear');
    Route::post('/settings/cache/optimize', [SettingsController::class, 'optimizeCache'])->name('settings.cache.optimize');

    // Standalone Profile Page (fully independent from settings)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Admin 2FA self-enrollment (standalone from Settings, mirrors /profile pattern)
    Route::prefix('profile/2fa')->name('profile.2fa.')->group(function () {
        Route::get('/', [TwoFactorProfileController::class, 'show'])->name('show');
        Route::post('/enable', [TwoFactorProfileController::class, 'enable'])->name('enable');
        Route::post('/confirm', [TwoFactorProfileController::class, 'confirm'])
            ->middleware('throttle:admin-2fa')->name('confirm');
        Route::post('/disable', [TwoFactorProfileController::class, 'disable'])
            ->middleware('throttle:admin-2fa')->name('disable');
        Route::post('/recovery', [TwoFactorProfileController::class, 'recovery'])
            ->middleware('throttle:admin-2fa')->name('recovery');
    });

    // Agents Management
    Route::prefix('agents')->name('agents.')->group(function () {
        // Agent Documents — must be before {agent} wildcard
        Route::get('/documents', [AgentDocumentController::class, 'index'])->name('documents');
        Route::post('/documents/{document}/approve', [AgentDocumentController::class, 'approve'])->name('documents.approve');
        Route::post('/documents/{document}/reject', [AgentDocumentController::class, 'reject'])->name('documents.reject');
        Route::get('/documents/{agent}', [AgentDocumentController::class, 'show'])->name('documents.show');

        Route::get('/kpis', [AdminAgentController::class, 'kpis'])->name('kpis');
        Route::get('/export', [AdminAgentController::class, 'export'])->name('export');
        Route::get('/', [AdminAgentController::class, 'index'])->name('index');
        Route::get('/create', [AdminAgentController::class, 'create'])->name('create');
        Route::post('/', [AdminAgentController::class, 'store'])->name('store');
        Route::get('/{agent}', [AdminAgentController::class, 'show'])->name('show');
        Route::get('/{agent}/dashboard', [AdminAgentController::class, 'dashboard'])->name('dashboard');
        Route::get('/{agent}/edit', [AdminAgentController::class, 'edit'])->name('edit');
        Route::put('/{agent}', [AdminAgentController::class, 'update'])->name('update');
        Route::delete('/{agent}', [AdminAgentController::class, 'destroy'])->name('destroy');
        Route::post('/{agent}/toggle-status', [AdminAgentController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Companies Management (الشركات — توزيع الرواتب)
    Route::prefix('companies')->name('companies.')->group(function () {
        // Company Documents — must be before {company} wildcard
        Route::get('/documents', [\App\Http\Controllers\Admin\CompanyDocumentController::class, 'index'])->name('documents');
        Route::post('/documents/{document}/approve', [\App\Http\Controllers\Admin\CompanyDocumentController::class, 'approve'])->name('documents.approve');
        Route::post('/documents/{document}/reject', [\App\Http\Controllers\Admin\CompanyDocumentController::class, 'reject'])->name('documents.reject');
        Route::get('/documents/{company}', [\App\Http\Controllers\Admin\CompanyDocumentController::class, 'show'])->name('documents.show');

        Route::get('/kpis', [\App\Http\Controllers\Admin\CompanyController::class, 'kpis'])->name('kpis');
        Route::get('/export', [\App\Http\Controllers\Admin\CompanyController::class, 'export'])->name('export');
        Route::get('/', [\App\Http\Controllers\Admin\CompanyController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\CompanyController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\CompanyController::class, 'store'])->name('store');
        Route::get('/{company}', [\App\Http\Controllers\Admin\CompanyController::class, 'show'])->name('show');
        Route::get('/{company}/edit', [\App\Http\Controllers\Admin\CompanyController::class, 'edit'])->name('edit');
        Route::put('/{company}', [\App\Http\Controllers\Admin\CompanyController::class, 'update'])->name('update');
        Route::post('/{company}/topup', [\App\Http\Controllers\Admin\CompanyController::class, 'topup'])->name('topup');
        Route::delete('/{company}', [\App\Http\Controllers\Admin\CompanyController::class, 'destroy'])->name('destroy');
        Route::post('/{company}/toggle-status', [\App\Http\Controllers\Admin\CompanyController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Fees Management (Dedicated Page)
    Route::prefix('fees')->name('fees.')->group(function () {
        Route::get('/', [FeeController::class, 'index'])->name('index');
        Route::put('/{code}', [FeeController::class, 'update'])->name('update');
        Route::patch('/{code}/toggle', [FeeController::class, 'toggle'])->name('toggle');
        Route::post('/preview', [FeeController::class, 'preview'])->name('preview');
    });

    // Support Tickets — customer support desk (ticket inbox + thread + reply)
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [SupportTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('status');
        Route::post('/{ticket}/assign', [SupportTicketController::class, 'assign'])->name('assign');
    });

    // Integrations — بسيطة: لوحة + عرض + حفظ + اختبار
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', [IntegrationController::class, 'overview'])->name('overview');
        Route::put('/{integration}', [IntegrationController::class, 'update'])->name('update');
        Route::post('/{integration}/test', [IntegrationController::class, 'test'])->name('test');
        Route::post('/{integration}/toggle', [IntegrationController::class, 'toggle'])->name('toggle');
    });

    // Merchants — التجار ونظام الدفع الخارجي
    Route::prefix('merchants')->name('merchants.')->group(function () {
        // Merchant Documents — must be before {merchant} wildcard
        Route::get('/documents', [MerchantDocumentController::class, 'index'])->name('documents');
        Route::post('/documents/{document}/approve', [MerchantDocumentController::class, 'approve'])->name('documents.approve');
        Route::post('/documents/{document}/reject', [MerchantDocumentController::class, 'reject'])->name('documents.reject');
        Route::get('/documents/{merchant}', [MerchantDocumentController::class, 'show'])->name('documents.show');

        Route::get('/kpis', [MerchantController::class, 'kpis'])->name('kpis');
        Route::get('/export', [MerchantController::class, 'export'])->name('export');
        Route::get('/', [MerchantController::class, 'index'])->name('index');
        Route::get('/create', [MerchantController::class, 'create'])->name('create');
        Route::post('/', [MerchantController::class, 'store'])->name('store');
        Route::get('/{merchant}', [MerchantController::class, 'show'])->name('show');
        Route::get('/{merchant}/dashboard', [MerchantController::class, 'dashboard'])->name('dashboard');
        Route::get('/{merchant}/edit', [MerchantController::class, 'edit'])->name('edit');
        Route::put('/{merchant}', [MerchantController::class, 'update'])->name('update');
        Route::delete('/{merchant}', [MerchantController::class, 'destroy'])->name('destroy');
        Route::post('/{merchant}/regenerate-keys', [MerchantController::class, 'regenerateKeys'])->name('regenerate-keys');
        Route::post('/{merchant}/toggle-status', [MerchantController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Gold Savings Management — merged dashboard (prices + recent transactions)
    Route::prefix('gold')->name('gold.')->group(function () {
        Route::get('/', [GoldPriceController::class, 'index'])->name('index');
        Route::get('/prices', fn() => redirect()->route('admin.gold.index'))->name('prices');
        Route::post('/prices', [GoldPriceController::class, 'store'])->name('price.store');
        Route::put('/prices/{goldPrice}', [GoldPriceController::class, 'update'])->name('price.update');
        Route::post('/prices/{goldPrice}/toggle', [GoldPriceController::class, 'toggleActive'])->name('price.toggle');
        Route::post('/prices/auto', [GoldPriceController::class, 'autoSettings'])->name('price.auto');
        Route::post('/prices/refresh', [GoldPriceController::class, 'refresh'])->name('price.refresh');
        Route::get('/transactions', [GoldPriceController::class, 'transactions'])->name('transactions');
    });

    // Live Chat — agent-side support chat (polling). Standalone, not the ticket desk.
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [LiveChatController::class, 'index'])->name('index');
        Route::get('/feed', [LiveChatController::class, 'inboxFeed'])->name('feed');
        Route::get('/{conversation}', [LiveChatController::class, 'show'])->name('show');
        Route::get('/{conversation}/poll', [LiveChatController::class, 'poll'])->name('poll');
        Route::post('/{conversation}/reply', [LiveChatController::class, 'reply'])->name('reply');
        Route::post('/{conversation}/status', [LiveChatController::class, 'setStatus'])->name('status');
    });

    // Notifications & Marketing — compose + broadcast push campaigns to audiences
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [PushNotificationController::class, 'index'])->name('index');
        Route::post('/send', [PushNotificationController::class, 'send'])->name('send');
    });

    // Admin alert bell (notifications TO admins). The list renders in the topbar;
    // these endpoints handle the read/dismiss actions.
    Route::post('/alerts/read-all', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('alerts.read-all');
    Route::post('/alerts/{alert}/dismiss', [\App\Http\Controllers\Admin\NotificationController::class, 'dismiss'])->name('alerts.dismiss');

    // System Configuration
    // 1) notification channels  2) message templates  3) maintenance & SEO
    Route::prefix('system')->name('system.')->group(function () {
        // Legacy redirect — services moved to integrations page
        Route::get('/third-party', fn () => redirect()->route('admin.integrations.overview'))->name('third-party');
        Route::put('/services/{service}', [SystemConfigController::class, 'updateService'])->name('services.update');
        Route::post('/services/{service}/test', [SystemConfigController::class, 'testService'])->name('services.test');

        // WhatsApp (OpenWA) link panel — live status + QR for linking the OTP number
        Route::get('/whatsapp/status', [SystemConfigController::class, 'whatsappStatus'])->name('whatsapp.status');
        Route::post('/whatsapp/link', [SystemConfigController::class, 'whatsappLink'])->name('whatsapp.link');

        // Telegram OTP channel — bot status + one-click webhook registration
        Route::get('/telegram/status', [SystemConfigController::class, 'telegramStatus'])->name('telegram.status');
        Route::post('/telegram/set-webhook', [SystemConfigController::class, 'telegramSetWebhook'])->name('telegram.set-webhook');

        // Section 2 — notification channels matrix
        Route::get('/channels', [SystemConfigController::class, 'channels'])->name('channels');
        Route::put('/channels', [SystemConfigController::class, 'updateChannels'])->name('channels.update');

        // Section 3 — message templates
        Route::get('/messages', [SystemConfigController::class, 'messages'])->name('messages');
        Route::put('/messages/{template}', [SystemConfigController::class, 'updateMessage'])->name('messages.update');

        // Section 4 — maintenance & data cleanup
        Route::get('/maintenance', [SystemConfigController::class, 'maintenance'])->name('maintenance');
        Route::post('/maintenance/clean', [SystemConfigController::class, 'cleanDatabase'])->name('maintenance.clean');

        // Section 5 — mobile force-update policy (min build + download URL)
        Route::get('/app-update', [AppUpdateController::class, 'index'])->name('app-update');
        Route::put('/app-update', [AppUpdateController::class, 'update'])->name('app-update.update');

        // Section 6 — technical support contact channels (served to the app)
        Route::get('/support', [SupportController::class, 'index'])->name('support');
        Route::put('/support', [SupportController::class, 'update'])->name('support.update');

        // Section 7 — system health monitoring (controller exists, route was missing)
        Route::get('/health', [SystemHealthController::class, 'index'])->name('health');
        Route::get('/health/checks', [SystemHealthController::class, 'runChecks'])->name('health.checks');

        // Section 8 — database backup management (controller exists, route was missing)
        Route::get('/backup', [DatabaseBackupController::class, 'index'])->name('backup');
        Route::post('/backup/create', [DatabaseBackupController::class, 'create'])->name('backup.create');
        Route::get('/backup/{filename}/download', [DatabaseBackupController::class, 'download'])->name('backup.download');
        Route::delete('/backup/{filename}', [DatabaseBackupController::class, 'delete'])->name('backup.delete');
        Route::post('/backup/{filename}/restore', [DatabaseBackupController::class, 'restore'])->name('backup.restore');
    });

    // KYC management (redirects to users page with KYC filter)
    Route::get('/kyc', fn () => redirect()->route('admin.users', ['kyc_status' => 'submitted']))->name('kyc.index');

    // Withdrawals management (views exist, routes were missing)
    Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
        Route::get('/', fn () => view('admin.withdrawals.index'))->name('index');
        Route::get('/{withdrawal}', fn () => view('admin.withdrawals.show'))->name('show');
    });

    // Audit log (controller exists, routes were missing)
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('index');
        Route::get('/{log}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('show');
        Route::get('/export', [\App\Http\Controllers\Admin\AuditLogController::class, 'export'])->name('export');
    });
});

// Webhooks (No auth required - external services)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    // CCPayment Webhooks
    Route::post('/ccpayment/deposit', [\App\Http\Controllers\Webhooks\CCPaymentWebhookController::class, 'deposit'])->name('ccpayment.deposit');
    Route::post('/ccpayment/withdraw', [\App\Http\Controllers\Webhooks\CCPaymentWebhookController::class, 'withdraw'])->name('ccpayment.withdraw');
    
    // CCPayment Test/Development Endpoints (local only)
    Route::get('/ccpayment/info', [\App\Http\Controllers\Webhooks\CCPaymentWebhookController::class, 'info'])->name('ccpayment.info');
    Route::post('/ccpayment/test/deposit', [\App\Http\Controllers\Webhooks\CCPaymentWebhookController::class, 'testDeposit'])->name('ccpayment.test.deposit');
    Route::post('/ccpayment/test/withdraw', [\App\Http\Controllers\Webhooks\CCPaymentWebhookController::class, 'testWithdraw'])->name('ccpayment.test.withdraw');
});

// Payment-request link landing page: {pay_url_base}/{uuid}.
// Doubles as the Android App Link target (https://<host>/pay/{uuid}) — when the
// app is installed + verified the OS opens it straight on the pay screen and
// this page never renders; otherwise it's the pretty fallback that shows the
// request and offers "open in app & pay" + APK download.
Route::get('/pay/{uuid}', [\App\Http\Controllers\PayLinkController::class, 'show'])
    ->where('uuid', '[0-9a-fA-F-]{8,}')
    ->name('pay.show');

// Fallback login route for Laravel's auth middleware redirect
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

/*
|--------------------------------------------------------------------------
| Company self-service portal (الشركات — توزيع الرواتب)
|--------------------------------------------------------------------------
| Operator logs in with their normal SAKK user account; the `company`
| middleware resolves THE company they own and scopes every page to it.
*/
Route::prefix('company')->name('company.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Company\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Company\AuthController::class, 'login'])
        ->middleware('throttle:company-login')->name('login.submit');
    Route::post('/logout', [\App\Http\Controllers\Company\AuthController::class, 'logout'])->name('logout');
});

Route::prefix('company')->name('company.')->middleware(['company'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Company\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/employees', [\App\Http\Controllers\Company\EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [\App\Http\Controllers\Company\EmployeeController::class, 'store'])->name('employees.store');
    Route::post('/employees/import', [\App\Http\Controllers\Company\EmployeeController::class, 'import'])
        ->middleware('block-dangerous-uploads')->name('employees.import');
    Route::delete('/employees/{employee}', [\App\Http\Controllers\Company\EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::get('/wallet', [\App\Http\Controllers\Company\WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [\App\Http\Controllers\Company\WalletController::class, 'topup'])->name('wallet.topup');

    Route::get('/payroll', [\App\Http\Controllers\Company\PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/create', [\App\Http\Controllers\Company\PayrollController::class, 'create'])->name('payroll.create');
    Route::post('/payroll', [\App\Http\Controllers\Company\PayrollController::class, 'store'])->name('payroll.store');
    Route::get('/payroll/{batch}', [\App\Http\Controllers\Company\PayrollController::class, 'show'])->name('payroll.show');
    Route::post('/payroll/{batch}/run', [\App\Http\Controllers\Company\PayrollController::class, 'run'])->name('payroll.run');
});

/*
|--------------------------------------------------------------------------
| Merchant self-service portal (التجار)
|--------------------------------------------------------------------------
*/
Route::prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Merchant\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Merchant\AuthController::class, 'login'])->middleware('throttle:merchant-login')->name('login.submit');
    Route::post('/logout', [\App\Http\Controllers\Merchant\AuthController::class, 'logout'])->name('logout');
});
Route::prefix('merchant')->name('merchant.')->middleware(['merchant'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Merchant\PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [\App\Http\Controllers\Merchant\PortalController::class, 'profile'])->name('profile');
    Route::post('/profile/keys', [\App\Http\Controllers\Merchant\PortalController::class, 'regenerateKeys'])->name('keys.regenerate');
    Route::get('/documents', [\App\Http\Controllers\Merchant\PortalController::class, 'documents'])->name('documents');
    Route::post('/documents', [\App\Http\Controllers\Merchant\PortalController::class, 'uploadDocument'])->middleware('block-dangerous-uploads')->name('documents.upload');
});

/*
|--------------------------------------------------------------------------
| Agent self-service portal (الوكلاء)
|--------------------------------------------------------------------------
*/
Route::prefix('agent')->name('agent.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Agent\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Agent\AuthController::class, 'login'])->middleware('throttle:agent-login')->name('login.submit');
    Route::post('/logout', [\App\Http\Controllers\Agent\AuthController::class, 'logout'])->name('logout');
});
Route::prefix('agent')->name('agent.')->middleware(['agent'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Agent\PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [\App\Http\Controllers\Agent\PortalController::class, 'profile'])->name('profile');
    Route::get('/documents', [\App\Http\Controllers\Agent\PortalController::class, 'documents'])->name('documents');
    Route::post('/documents', [\App\Http\Controllers\Agent\PortalController::class, 'uploadDocument'])->middleware('block-dangerous-uploads')->name('documents.upload');
});

// Referral invite landing page: {invite_url_base}/{code}.
// Doubles as the Android App Link target (https://<host>/invite/{code}) — when
// the app is installed + verified the OS opens it directly and this never renders.
Route::get('/invite/{code}', [InviteController::class, 'show'])
    ->where('code', '[A-Za-z0-9@#_-]{1,32}')
    ->name('invite');

// Android App Links digital-asset link. Served as a static file at
// public/.well-known/assetlinks.json; this route is a fallback in case the web
// server routes the request through Laravel instead of serving the file.
Route::get('/.well-known/assetlinks.json', function () {
    $path = public_path('.well-known/assetlinks.json');
    abort_unless(is_file($path), 404);
    return response()->file($path, ['Content-Type' => 'application/json']);
});

// Legal pages — privacy policy + terms of service (rendered blade views, not hardcoded)
Route::get('/legal/privacy', function () {
    return view('legal.privacy');
})->name('legal.privacy');

Route::get('/legal/terms', function () {
    return view('legal.terms');
})->name('legal.terms');

// Landing page removed per user request
