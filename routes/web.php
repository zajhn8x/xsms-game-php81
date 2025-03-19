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

Route::get('/lottery', function () {
    return view('lottery.index');
});

Route::get('/bet', function () {
    return view('bet.form');
});

Route::post('/bet', [App\Http\Controllers\BetController::class, 'store']);
Route::get('/statistics', function () {
    return view('statistics.index');
});