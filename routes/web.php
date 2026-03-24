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

use App\Http\Controllers\ErrorDemoController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('error-demo')->group(function () {
    Route::get('/exception', [ErrorDemoController::class, 'throwException']);
    Route::get('/divide-by-zero', [ErrorDemoController::class, 'divideByZero']);
    Route::get('/capture-message', [ErrorDemoController::class, 'captureMessage']);
});

Route::get('/debug-sentry', function () {
    throw new Exception('My first Sentry error!');
});
