<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [App\Http\Controllers\LotteryController::class, 'index'])->name('home');
Route::get('/lottery', [App\Http\Controllers\LotteryController::class, 'index'])->name('lottery.index');

Route::get('/bet', function () {
    return view('bet.form');
});

Route::post('/bet', [App\Http\Controllers\BetController::class, 'store'])->name('bet.store');
Route::get('/statistics', [App\Http\Controllers\StatisticsController::class, 'index'])->name('statistics.index');