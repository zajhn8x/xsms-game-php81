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

Route::get('/', function () {
    return redirect('/lottery');
});

Route::get('/lottery', [App\Http\Controllers\LotteryController::class, 'index']);
Route::get('/bet', [App\Http\Controllers\BetController::class, 'form']);
Route::post('/bet', [App\Http\Controllers\BetController::class, 'store']);
Route::get('/statistics', [App\Http\Controllers\StatisticsController::class, 'index']);
