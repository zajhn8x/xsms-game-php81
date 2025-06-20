<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LotteryResultController;
use App\Http\Controllers\Api\AuthController;

Route::get('/lottery-results', [LotteryResultController::class, 'index']);
Route::get('/lottery-results/date-range', [LotteryResultController::class, 'getByDateRange']);

/*
|--------------------------------------------------------------------------
| Authentication API Routes
|--------------------------------------------------------------------------
*/

// Public authentication routes
Route::prefix('auth')->name('api.auth.')->group(function () {
    // Guest routes (no authentication required)
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/verify-2fa', [AuthController::class, 'verifyTwoFactor'])->name('verify-2fa');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

    // Authenticated routes (require auth:sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password');
        Route::post('/update-profile', [AuthController::class, 'updateProfile'])->name('update-profile');
        Route::get('/sessions', [AuthController::class, 'sessions'])->name('sessions');
        Route::delete('/sessions/{tokenId}', [AuthController::class, 'revokeSession'])->name('revoke-session');
    });
});

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
