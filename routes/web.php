<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LotteryController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\CauLoController;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\HeatmapAnalyticController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\HistoricalTestingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\RiskManagementController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\CampaignTemplateController;
use App\Http\Controllers\SubCampaignController;
use App\Http\Controllers\PerformanceAlertController;
use App\Http\Controllers\ResourceMonitoringController;

Route::get('/', [LotteryController::class, 'index'])->name('home');
Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');

// Compass Theme Examples (Development Only)
if (app()->environment('local')) {
    Route::get('/compass-theme', function () {
        return view('compass-theme-examples');
    })->name('compass.theme');
}

// Authentication routes
\Illuminate\Support\Facades\Auth::routes();

// Social Authentication routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/{provider}/redirect', [App\Http\Controllers\Auth\SocialAuthController::class, 'redirectToProvider'])
        ->name('social.redirect')
        ->where('provider', 'google|facebook');

    Route::get('/{provider}/callback', [App\Http\Controllers\Auth\SocialAuthController::class, 'handleProviderCallback'])
        ->name('social.callback')
        ->where('provider', 'google|facebook');

    // Protected routes for linking/unlinking accounts
    Route::middleware('auth')->group(function () {
        Route::post('/{provider}/link', [App\Http\Controllers\Auth\SocialAuthController::class, 'linkAccount'])
            ->name('social.link')
            ->where('provider', 'google|facebook');

        Route::delete('/{provider}/unlink', [App\Http\Controllers\Auth\SocialAuthController::class, 'unlinkAccount'])
            ->name('social.unlink')
            ->where('provider', 'google|facebook');
    });
});

Route::middleware('auth')->group(function () {
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');

    // Dashboard API routes
    Route::prefix('api/dashboard')->group(function () {
        Route::get('/user', [DashboardController::class, 'apiUserDashboard']);
        Route::get('/admin', [DashboardController::class, 'apiAdminDashboard']);
        Route::get('/chart/{type}', [DashboardController::class, 'apiChartData']);
        Route::get('/admin/chart/{type}', [DashboardController::class, 'apiAdminChartData']);
    });

    // Wallet routes
    Route::prefix('wallet')->name('wallet.')->middleware('rate.limit:financial')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::post('/deposit', [WalletController::class, 'deposit'])->name('deposit');
        Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('withdraw');
        Route::post('/transfer', [WalletController::class, 'transfer'])->name('transfer');
        Route::get('/history', [WalletController::class, 'history'])->name('history');

        // Payment gateway return URLs
        Route::get('/vnpay/return', [WalletController::class, 'vnpayReturn'])->name('vnpay.return');
        Route::get('/momo/return', [WalletController::class, 'momoReturn'])->name('momo.return');

        // Admin routes
        Route::post('/admin/withdrawal/{transactionId}/process', [WalletController::class, 'adminProcessWithdrawal'])
            ->name('admin.withdrawal.process');
    });

    // Historical Testing routes
    Route::prefix('historical-testing')->name('historical-testing.')->group(function () {
        Route::get('/', [HistoricalTestingController::class, 'index'])->name('index');
        Route::get('/create', [HistoricalTestingController::class, 'create'])->name('create');
        Route::post('/', [HistoricalTestingController::class, 'store'])->name('store');
        Route::get('/{id}', [HistoricalTestingController::class, 'show'])->name('show');
        Route::post('/{id}/run', [HistoricalTestingController::class, 'run'])->name('run');
        Route::get('/{id}/status', [HistoricalTestingController::class, 'status'])->name('status');
        Route::delete('/{id}', [HistoricalTestingController::class, 'destroy'])->name('destroy');

        // API routes
        Route::prefix('api')->group(function () {
            Route::get('/', [HistoricalTestingController::class, 'apiIndex']);
            Route::get('/{id}/bets', [HistoricalTestingController::class, 'apiBetHistory']);
            Route::get('/{id}/results', [HistoricalTestingController::class, 'apiResults']);
        });

        // Strategy helper
        Route::get('/strategy/config', [HistoricalTestingController::class, 'getStrategyConfig'])
            ->name('strategy.config');
    });

    // Campaign routes
    Route::resource('campaigns', CampaignController::class)->middleware('rate.limit:campaign');
    Route::get('campaigns/analytics', function () {
        return view('campaigns.analytics');
    })->name('campaigns.analytics');
    Route::get('campaigns/{campaign}/bet', [CampaignController::class, 'showBetForm'])->name('campaigns.bet.form');
    Route::post('campaigns/{campaign}/bet', [CampaignController::class, 'placeBet'])->name('campaigns.bet')->middleware('rate.limit:campaign');

    // Phase 2: Social Features
    Route::prefix('social')->name('social.')->group(function () {
        Route::get('/', [SocialController::class, 'index'])->name('index');
        Route::post('/follow/{user}', [SocialController::class, 'follow'])->name('follow');
        Route::delete('/unfollow/{user}', [SocialController::class, 'unfollow'])->name('unfollow');
        Route::get('/followers/{user}', [SocialController::class, 'followers'])->name('followers');
        Route::get('/following/{user}', [SocialController::class, 'following'])->name('following');
        Route::get('/profile/{user}', [SocialController::class, 'profile'])->name('profile');
        Route::get('/leaderboard', [SocialController::class, 'leaderboard'])->name('leaderboard');

        // API endpoints
        Route::prefix('api')->group(function () {
            Route::get('/feed', [SocialController::class, 'feed']);
            Route::get('/following-campaigns', [SocialController::class, 'followingCampaigns']);
            Route::get('/top-performers', [SocialController::class, 'topPerformers']);
            Route::get('/search-users', [SocialController::class, 'searchUsers']);
            Route::get('/recommend-campaigns', [SocialController::class, 'recommendCampaigns']);
        });
    });

    // Campaign sharing
    Route::post('/campaigns/{campaign}/share', [SocialController::class, 'shareCampaign'])->name('campaigns.share');
    Route::get('/api/campaigns/{campaign}/share-analytics', [SocialController::class, 'shareAnalytics'])->name('api.campaigns.share-analytics');

    // Phase 2: Risk Management
    Route::prefix('risk-management')->name('risk-management.')->group(function () {
        Route::get('/', [RiskManagementController::class, 'index'])->name('index');
        Route::post('/', [RiskManagementController::class, 'store'])->name('store');
        Route::get('/{riskRule}', [RiskManagementController::class, 'show'])->name('show');
        Route::put('/{riskRule}', [RiskManagementController::class, 'update'])->name('update');
        Route::delete('/{riskRule}', [RiskManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{riskRule}/toggle', [RiskManagementController::class, 'toggle'])->name('toggle');
        Route::post('/setup-defaults', [RiskManagementController::class, 'setupDefaults'])->name('setup-defaults');

        // API endpoints
        Route::prefix('api')->group(function () {
            Route::get('/check', [RiskManagementController::class, 'checkRisk']);
            Route::get('/rule-types', [RiskManagementController::class, 'ruleTypes']);
            Route::get('/templates', [RiskManagementController::class, 'ruleTemplates']);
            Route::get('/statistics', [RiskManagementController::class, 'statistics']);
        });
    });

    // Payment Gateway Webhooks (outside auth middleware)
});

// Public webhook routes (no authentication required)
Route::prefix('webhook')->name('webhook.')->group(function () {
    Route::post('/vnpay/notify', [App\Http\Controllers\WebhookController::class, 'vnpayNotify'])->name('vnpay.notify');
    Route::post('/momo/notify', [App\Http\Controllers\WebhookController::class, 'momoNotify'])->name('momo.notify');
});

Route::middleware('auth')->group(function () {
    // Two-Factor Authentication routes
    Route::prefix('two-factor')->name('two-factor.')->middleware('rate.limit:2fa')->group(function () {
        Route::get('/', [TwoFactorController::class, 'index'])->name('index');
        Route::post('/enable-totp', [TwoFactorController::class, 'enableTotp'])->name('enable-totp');
        Route::post('/confirm-totp', [TwoFactorController::class, 'confirmTotp'])->name('confirm-totp');
        Route::delete('/disable', [TwoFactorController::class, 'disable'])->name('disable');
        Route::post('/send-sms', [TwoFactorController::class, 'sendSmsToken'])->name('send-sms');
        Route::post('/send-email', [TwoFactorController::class, 'sendEmailToken'])->name('send-email');
        Route::post('/recovery-codes', [TwoFactorController::class, 'generateRecoveryCodes'])->name('recovery-codes');
        Route::get('/challenge', [TwoFactorController::class, 'showChallenge'])->name('challenge');
        Route::post('/challenge', [TwoFactorController::class, 'verifyChallenge'])->name('verify-challenge');
        Route::get('/qr-image', [TwoFactorController::class, 'qrImage'])->name('qr-image');
    });
});

Route::prefix('caulo')->group(function () {
    Route::get('/heatmap', [HeatmapController::class, 'index'])->name('heatmap.index');
    Route::get('/find', [CauLoController::class, 'find'])->name('caulo.find');
    Route::get('/search', [CauLoController::class, 'search'])->name('caulo.search');
    Route::get('/timeline/{id}', [CauLoController::class, 'timeline'])->name('caulo.timeline');
    Route::get('/heatmap-analytic/{date?}', [HeatmapAnalyticController::class, 'index'])->name('heatmap.analytic');
});

// Public campaign sharing routes (no auth required)
Route::get('/campaigns/{campaign}/shared', [SocialController::class, 'sharedCampaign'])->name('campaigns.shared');

// Legacy campaign routes (keeping for backward compatibility)
Route::prefix('campaigns')->group(function () {
    Route::get('/', [\App\Http\Controllers\CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/create', [\App\Http\Controllers\CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/', [\App\Http\Controllers\CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/{campaign}', [\App\Http\Controllers\CampaignController::class, 'show'])->name('campaigns.show');
    Route::post('/{campaign}/run', [\App\Http\Controllers\CampaignController::class, 'run'])->name('campaigns.run');
    Route::post('/{campaign}/pause', [\App\Http\Controllers\CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('/{campaign}/finish', [\App\Http\Controllers\CampaignController::class, 'finish'])->name('campaigns.finish');
    Route::delete('/{campaign}', [\App\Http\Controllers\CampaignController::class, 'destroy'])->name('campaigns.destroy');
    Route::post('/{campaign}/bets', [\App\Http\Controllers\CampaignController::class, 'storeBet'])->name('campaigns.bets.store');
});

// Campaign Template routes - Micro-task 2.1.2.6: Template sharing system (2h)
Route::middleware(['auth'])->group(function () {
    Route::resource('campaign-templates', CampaignTemplateController::class);

    // Template management actions
    Route::post('/campaign-templates/{template}/duplicate', [CampaignTemplateController::class, 'duplicate'])
        ->name('campaign-templates.duplicate');
    Route::post('/campaign-templates/{template}/create-campaign', [CampaignTemplateController::class, 'createCampaign'])
        ->name('campaign-templates.create-campaign');

    // Template sharing & rating
    Route::post('/campaign-templates/{template}/rate', [CampaignTemplateController::class, 'rate'])
        ->name('campaign-templates.rate');
    Route::get('/campaign-templates/{template}/export', [CampaignTemplateController::class, 'export'])
        ->name('campaign-templates.export');
    Route::post('/campaign-templates/import', [CampaignTemplateController::class, 'import'])
        ->name('campaign-templates.import');

    // AJAX endpoints for templates
    Route::get('/api/campaign-templates/popular', [CampaignTemplateController::class, 'popular'])
        ->name('campaign-templates.popular');
    Route::get('/api/campaign-templates/search', [CampaignTemplateController::class, 'search'])
        ->name('campaign-templates.search');
});

// Sub-campaign routes
Route::middleware(['auth'])->group(function () {
    Route::prefix('campaigns/{campaign}/sub-campaigns')->name('campaigns.sub-campaigns.')->group(function () {
        Route::get('/', [SubCampaignController::class, 'index'])->name('index');
        Route::post('/', [SubCampaignController::class, 'store'])->name('store');
        Route::post('/split', [SubCampaignController::class, 'split'])->name('split');
        Route::get('/monitoring', [SubCampaignController::class, 'monitoring'])->name('monitoring');
        Route::post('/rebalance', [SubCampaignController::class, 'rebalance'])->name('rebalance');
        Route::get('/performance-data', [SubCampaignController::class, 'performanceData'])->name('performance-data');

        Route::prefix('{subCampaign}')->group(function () {
            Route::get('/', [SubCampaignController::class, 'show'])->name('show');
            Route::post('/start', [SubCampaignController::class, 'start'])->name('start');
            Route::post('/pause', [SubCampaignController::class, 'pause'])->name('pause');
            Route::post('/resume', [SubCampaignController::class, 'resume'])->name('resume');
            Route::post('/stop', [SubCampaignController::class, 'stop'])->name('stop');
            Route::delete('/', [SubCampaignController::class, 'destroy'])->name('destroy');
        });
    });

    // Performance Alerts routes
    Route::prefix('performance-alerts')->name('performance-alerts.')->group(function () {
        Route::get('/overview', [PerformanceAlertController::class, 'getUserAlertsOverview'])->name('overview');
        Route::get('/configuration', [PerformanceAlertController::class, 'getAlertConfiguration'])->name('configuration');
        Route::get('/metrics', [PerformanceAlertController::class, 'getAlertMetrics'])->name('metrics');

        Route::prefix('campaigns/{campaign}')->name('campaigns.')->group(function () {
            Route::get('/alerts', [PerformanceAlertController::class, 'getCampaignAlerts'])->name('alerts');
            Route::get('/alert-history', [PerformanceAlertController::class, 'getCampaignAlertHistory'])->name('alert-history');
            Route::post('/acknowledge-alerts', [PerformanceAlertController::class, 'acknowledgeCampaignAlerts'])->name('acknowledge-alerts');
            Route::post('/test-alerts', [PerformanceAlertController::class, 'testCampaignAlerts'])->name('test-alerts');
        });
    });

    // Resource Monitoring routes - Micro-task 2.3.1.4: Resource usage monitoring (3h)
    Route::prefix('resource-monitoring')->name('resource-monitoring.')->group(function () {
        Route::get('/system/overview', [ResourceMonitoringController::class, 'getSystemOverview'])->name('system.overview');
        Route::get('/system/detailed', [ResourceMonitoringController::class, 'getDetailedBreakdown'])->name('system.detailed');
        Route::get('/alerts', [ResourceMonitoringController::class, 'getResourceAlerts'])->name('alerts');
        Route::get('/campaigns', [ResourceMonitoringController::class, 'getCampaignResourceUsage'])->name('campaigns');
        Route::get('/campaigns/{campaign}', [ResourceMonitoringController::class, 'getCampaignResourceUsage'])->name('campaigns.show');
        Route::get('/users', [ResourceMonitoringController::class, 'getUserResourceUsage'])->name('users');
        Route::get('/recommendations', [ResourceMonitoringController::class, 'getOptimizationRecommendations'])->name('recommendations');
        Route::get('/health-metrics', [ResourceMonitoringController::class, 'getHealthMetrics'])->name('health-metrics');
        Route::post('/refresh-cache', [ResourceMonitoringController::class, 'refreshCache'])->name('refresh-cache');
    });
});


