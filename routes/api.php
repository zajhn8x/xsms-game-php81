<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LotteryResultController;

Route::get('/lottery-results', [LotteryResultController::class, 'index']);
Route::get('/lottery-results/date-range', [LotteryResultController::class, 'getByDateRange']);

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/statistics', [App\Http\Controllers\Api\StatisticsController::class, 'index']);
    Route::get('/bet-history', [App\Http\Controllers\Api\StatisticsController::class, 'betHistory']);
    Route::get('/bet-trends', [App\Http\Controllers\Api\StatisticsController::class, 'trends']);
});

Route::prefix('v1')->group(function () {
    Route::apiResource('lottery-results', App\Http\Controllers\Api\LotteryResultController::class);
    Route::apiResource('lottery-cau-meta', App\Http\Controllers\Api\LotteryCauMetaController::class);
    Route::apiResource('lottery-cau-lo', App\Http\Controllers\Api\LotteryFormulaController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('lottery-bets', App\Http\Controllers\Api\LotteryBetController::class);
        Route::apiResource('lottery-logs', App\Http\Controllers\Api\LotteryLogController::class);
    });
});
