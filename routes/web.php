<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LotteryController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\CauLoController;

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
    Route::get('/find', [CauLoController::class, 'find'])->name('caulo.find');
    Route::get('/search', [CauLoController::class, 'search'])->name('caulo.search');
    Route::get('/timeline/{id}', [CauLoController::class, 'timeline'])->name('caulo.timeline');
});
