<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Syria Homes — API v1 Routes
|--------------------------------------------------------------------------
|
| Public → Auth → Agency → Admin
| Every group has its own middleware & prefix.
|
| Throttle defaults:
|   60/min API  |  10/min inquiries  |  5/min newsletter  |  30/min search
|
*/

use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\Agency\ChatController as AgencyChatController;
use App\Http\Controllers\Api\V1\Agency\DashboardController as AgencyDashboardController;
use App\Http\Controllers\Api\V1\Agency\QuickReplyController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\UserController;
use App\Http\Controllers\Api\V1\AgenciesController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\GuestChatController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\NewsletterController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\PropertyTypeController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SakkWebhookController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Controllers\Api\V1\TestimonialController;
use App\Http\Controllers\Api\V1\UserChatController;
use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    // =========================================================================
    // INSTALLER (no auth, not blocked by InstallerGuard)
    // =========================================================================
    Route::prefix('install')->group(function (): void {
        Route::get('requirements', [InstallController::class, 'requirements']);
        Route::get('database',     [InstallController::class, 'database']);
        Route::post('database',    [InstallController::class, 'databaseStore']);
        Route::get('admin',        [InstallController::class, 'admin']);
        Route::post('admin',       [InstallController::class, 'adminStore']);
        Route::get('settings',     [InstallController::class, 'settings']);
        Route::post('settings',    [InstallController::class, 'settingsStore']);
        Route::get('complete',     [InstallController::class, 'complete']);
    });

    // =========================================================================
    // PUBLIC (no auth required)
    // =========================================================================

    // Reference data
    Route::get('property-types', [PropertyTypeController::class, 'index']);

    // Locations
    Route::get('locations', [LocationController::class, 'index']);
    Route::get('locations/{governorate}/areas', [LocationController::class, 'areas']);

    // Properties (read-only)
    Route::get('properties/featured',            [PropertyController::class, 'featured']);
    Route::get('properties/hot-deals',           [PropertyController::class, 'hotDeals']);
    Route::get('properties',                     [PropertyController::class, 'index']);
    Route::get('properties/{property:slug}',     [PropertyController::class, 'show']);
    Route::get('properties/chat-card/{property}', [PropertyController::class, 'chatCard']);

    // Inquiries (write, throttled)
    Route::post('properties/{property}/inquiries', [InquiryController::class, 'store'])
        ->middleware('throttle:10,1');

    // Agents & Agencies
    Route::get('agents/{agent}', [AgentController::class, 'show']);
    Route::get('agents',         [AgentController::class, 'index']);
    Route::get('agencies',              [AgenciesController::class, 'index']);
    Route::get('agencies/{slug}',        [AgenciesController::class, 'show']);
    Route::get('agencies/{id}/properties', [AgenciesController::class, 'properties']);

    // Social proof & stats
    Route::get('testimonials', [TestimonialController::class, 'index']);
    Route::get('stats',        [StatsController::class, 'show']);

    // Newsletter (write, throttled)
    Route::post('newsletter', [NewsletterController::class, 'store'])
        ->middleware('throttle:5,1');

    // Search
    Route::get('search/suggest', [SearchController::class, 'suggest'])
        ->middleware('throttle:30,1');

    // SAKK webhook (payment gateway callback)
    Route::post('sakk/webhook', SakkWebhookController::class)->name('sakk.webhook');

    // Guest chat (no auth required)
    Route::prefix('guest/chat')->group(function (): void {
        Route::post('start',                    [GuestChatController::class, 'start']);
        Route::get('{token}/messages',          [GuestChatController::class, 'messages']);
        Route::post('{token}/messages',         [GuestChatController::class, 'storeMessage'])
            ->middleware('throttle:30,1');
        Route::put('{token}/read',              [GuestChatController::class, 'markAsRead']);
    });

    // Contact form
    Route::post('contact', [ContactController::class, 'store'])
        ->middleware('throttle:5,1');

    // Public settings
    Route::get('settings/public', [SettingController::class, 'public']);

    // Reviews (read)
    Route::get('reviews', [ReviewController::class, 'index']);

    // =========================================================================
    // AUTH (requires Sanctum token)
    // =========================================================================
    // Auth — throttled against brute-force + abuse
    Route::post('auth/register',         [AuthController::class, 'register'])->middleware('throttle:3,1');
    Route::post('auth/agency-register',  [AuthController::class, 'agencyRegister'])->middleware('throttle:3,1');
    Route::post('auth/login',           [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('auth/forgot-password',  [AuthController::class, 'forgotPassword'])->middleware('throttle:3,60');
    Route::post('auth/reset-password',   [AuthController::class, 'resetPassword'])->middleware('throttle:3,60');

    Route::middleware('auth:sanctum')->group(function (): void {
        // Auth
        Route::post('auth/logout',         [AuthController::class, 'logout']);
        Route::get('auth/me',              [AuthController::class, 'me']);
        Route::put('auth/profile',         [AuthController::class, 'updateProfile']);
        Route::post('auth/avatar',         [AuthController::class, 'uploadAvatar']);
        Route::post('auth/change-password', [AuthController::class, 'changePassword']);

        // Favorites
        Route::get('favorites',             [UserController::class, 'favorites']);
        Route::post('favorites/toggle',     [UserController::class, 'toggleFavorite']);

        // Saved searches
        Route::get('saved-searches',        [UserController::class, 'savedSearches']);
        Route::post('saved-searches',       [UserController::class, 'saveSearch']);
        Route::delete('saved-searches/{search}', [UserController::class, 'deleteSearch']);

        // User dashboard
        Route::get('user/dashboard',  [UserController::class, 'dashboard']);
        Route::get('user/inquiries',  [UserController::class, 'inquiries']);

        // User chat
        Route::prefix('user/chat')->group(function (): void {
            Route::get('conversations',              [UserChatController::class, 'conversations']);
            Route::post('conversations',             [UserChatController::class, 'startConversation']);
            Route::get('conversations/{conversation}/messages', [UserChatController::class, 'messages']);
            Route::post('conversations/{conversation}/messages', [UserChatController::class, 'storeMessage']);
            Route::put('conversations/{conversation}/read',     [UserChatController::class, 'markAsRead']);
            Route::get('unread-count',               [UserChatController::class, 'unreadCount']);

            // Offer / Negotiation (client)
            Route::post('conversations/{conversation}/offer',         [UserChatController::class, 'sendOffer']);
            Route::post('conversations/{conversation}/offer/accept',  [UserChatController::class, 'acceptOffer']);
            Route::post('conversations/{conversation}/offer/reject',  [UserChatController::class, 'rejectOffer']);
            Route::post('conversations/{conversation}/offer/counter', [UserChatController::class, 'counterOffer']);

            // Archive / trash / restore
            Route::put('conversations/{conversation}/archive',       [UserChatController::class, 'archive']);
            Route::post('conversations/{id}/unarchive',              [UserChatController::class, 'unarchive'])->whereNumber('id');
            Route::delete('conversations/{conversation}/trash',      [UserChatController::class, 'trash']);
            // Restore/force use explicit id to support trashed models
            Route::post('conversations/{id}/restore',                [UserChatController::class, 'restore'])->whereNumber('id');
            Route::delete('conversations/{id}/force',                [UserChatController::class, 'forceDelete'])->whereNumber('id');
        });

        // Reviews (write)
        Route::post('reviews', [ReviewController::class, 'store']);

        // =====================================================================
        // AGENCY (requires auth + agency role)
        // =====================================================================
        Route::middleware('role:agency|admin')->prefix('agency')->group(function (): void {
            Route::get('dashboard/stats',        [AgencyDashboardController::class, 'stats']);
            Route::get('properties',             [AgencyDashboardController::class, 'properties']);
            Route::post('properties',            [AgencyDashboardController::class, 'storeProperty']);
            Route::put('properties/{property}',  [AgencyDashboardController::class, 'updateProperty']);
            Route::get('agents',                 [AgencyDashboardController::class, 'agents']);
            Route::post('agents',                [AgencyDashboardController::class, 'storeAgent']);
            Route::get('inquiries',              [AgencyDashboardController::class, 'inquiries']);
            Route::put('inquiries/{inquiry}',    [AgencyDashboardController::class, 'updateInquiry']);
            Route::get('subscription',           [AgencyDashboardController::class, 'subscription']);
            Route::post('subscription/subscribe', [AgencyDashboardController::class, 'subscribe']);
            Route::get('profile',                [AgencyDashboardController::class, 'profile']);
            Route::put('profile',                [AgencyDashboardController::class, 'updateProfile']);
            Route::post('logo',                  [AgencyDashboardController::class, 'uploadLogo']);
            Route::post('cover',                 [AgencyDashboardController::class, 'uploadCover']);
            Route::get('sakk-account',            [AgencyDashboardController::class, 'sakkAccount']);
            Route::post('sakk-account',           [AgencyDashboardController::class, 'updateSakkAccount']);
            Route::delete('sakk-account',         [AgencyDashboardController::class, 'removeSakkAccount']);
            Route::get('deals',                  [AgencyDashboardController::class, 'deals']);
            Route::post('deals',                 [AgencyDashboardController::class, 'storeDeal']);
            Route::put('deals/{deal}',           [AgencyDashboardController::class, 'updateDeal']);
            Route::get('payments',               [AgencyDashboardController::class, 'payments']);
            Route::get('commission-report',      [AgencyDashboardController::class, 'commissionReport']);
            Route::get('properties/{property}',  [AgencyDashboardController::class, 'showProperty']);
            Route::delete('properties/{property}', [AgencyDashboardController::class, 'destroyProperty']);
            Route::post('properties/{property}/images',        [AgencyDashboardController::class, 'uploadImages']);
            Route::delete('properties/{property}/images/{image}', [AgencyDashboardController::class, 'deleteImage']);
            Route::post('properties/{property}/images/{image}/cover', [AgencyDashboardController::class, 'setCoverImage']);

            // Chat
            Route::get('conversations',                    [AgencyChatController::class, 'conversations']);
            Route::get('conversations/{conversation}/messages', [AgencyChatController::class, 'messages']);
            Route::post('conversations/{conversation}/messages', [AgencyChatController::class, 'storeMessage']);
            Route::post('conversations/{conversation}/payment-request', [AgencyChatController::class, 'sendPaymentRequest'])
                ->middleware('throttle:10,1');
            Route::put('conversations/{conversation}/read',     [AgencyChatController::class, 'markAsRead']);
            Route::get('chat/unread-count',                   [AgencyChatController::class, 'unreadCount']);

            // Offer / Negotiation (agency)
            Route::post('conversations/{conversation}/offer',         [AgencyChatController::class, 'sendOffer']);
            Route::post('conversations/{conversation}/offer/accept',  [AgencyChatController::class, 'acceptOffer']);
            Route::post('conversations/{conversation}/offer/reject',  [AgencyChatController::class, 'rejectOffer']);
            Route::post('conversations/{conversation}/offer/counter', [AgencyChatController::class, 'counterOffer']);

            // Quick replies
            Route::get('quick-replies',           [QuickReplyController::class, 'index']);
            Route::post('quick-replies',          [QuickReplyController::class, 'store']);
            Route::get('quick-replies/{quickReply}', [QuickReplyController::class, 'show']);
            Route::put('quick-replies/{quickReply}', [QuickReplyController::class, 'update']);
            Route::delete('quick-replies/{quickReply}', [QuickReplyController::class, 'destroy']);
            Route::post('quick-replies/{quickReply}/preview', [QuickReplyController::class, 'preview']);
            Route::post('quick-replies/{quickReply}/send',    [QuickReplyController::class, 'send']);
        });

        // =====================================================================
        // ADMIN (requires admin role)
        // =====================================================================
        Route::middleware('role:admin')->prefix('admin')->group(function (): void {
            Route::get('dashboard',            [AdminController::class, 'dashboard']);
            Route::get('users',                [AdminController::class, 'users']);
            Route::put('users/{user}',         [AdminController::class, 'updateUser']);
            Route::get('agencies',             [AdminController::class, 'agencies']);
            Route::put('agencies/{agency}',    [AdminController::class, 'updateAgency']);
            Route::get('properties',           [AdminController::class, 'properties']);
            Route::put('properties/{property}/moderate', [AdminController::class, 'moderateProperty']);
            Route::get('plans',                [AdminController::class, 'subscriptionPlans']);
            Route::post('plans',               [AdminController::class, 'storePlan']);
            Route::put('plans/{plan}',         [AdminController::class, 'updatePlan']);
            Route::get('messages',             [AdminController::class, 'contactMessages']);
            Route::post('messages/{message}/read', [AdminController::class, 'readMessage']);
            Route::get('reviews',              [AdminController::class, 'reviews']);
            Route::post('reviews/{review}/approve', [AdminController::class, 'approveReview']);
            Route::get('settings',             [AdminController::class, 'settings']);
            Route::post('settings',            [AdminController::class, 'updateSettings']);
            Route::get('areas',                [AdminController::class, 'areas']);
            Route::post('areas',               [AdminController::class, 'storeArea']);
            Route::put('areas/{area}',         [AdminController::class, 'updateArea']);
            Route::delete('areas/{area}',      [AdminController::class, 'deleteArea']);
            Route::get('governorates',         [AdminController::class, 'governorates']);
        });
    });
});
