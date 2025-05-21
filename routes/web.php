<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LotteryController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\CauLoController;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\HeatmapAnalyticController;
use App\Http\Controllers\CampaignController;

Route::get('/', [LotteryController::class, 'index'])->name('home');
Route::get('/lottery', [LotteryController::class, 'index'])->name('lottery.index');

Route::get('/bet', [BetController::class, 'index'])->name('bet.index');
Route::post('/bet', [BetController::class, 'store'])->name('bet.store');
Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('campaigns', CampaignController::class);
    Route::get('campaigns/{campaign}/bet', [CampaignController::class, 'showBetForm'])->name('campaigns.bet.form');
    Route::post('campaigns/{campaign}/bet', [CampaignController::class, 'placeBet'])->name('campaigns.bet');
});

Route::prefix('caulo')->group(function () {
    Route::get('/heatmap', [HeatmapController::class, 'index'])->name('heatmap.index');
    Route::get('/find', [CauLoController::class, 'find'])->name('caulo.find');
    Route::get('/search', [CauLoController::class, 'search'])->name('caulo.search');
    Route::get('/timeline/{id}', [CauLoController::class, 'timeline'])->name('caulo.timeline');
    Route::get('/heatmap-analytic/{date?}', [HeatmapAnalyticController::class, 'index'])->name('heatmap.analytic');
});

// Campaign routes
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


