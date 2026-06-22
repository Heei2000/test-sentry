<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ErrorDemoController;
use App\Http\Controllers\BusinessErrorController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('error-demo')->group(function () {
    Route::get('/exception', [ErrorDemoController::class, 'throwException']);
    Route::get('/divide-by-zero', [ErrorDemoController::class, 'divideByZero']);
    Route::get('/capture-message', [ErrorDemoController::class, 'captureMessage']);
    Route::get('/null-pointer', [ErrorDemoController::class, 'nullPointerError']);
    Route::get('/array-out-of-bounds', [ErrorDemoController::class, 'arrayOutOfBounds']);
    Route::get('/type-error', [ErrorDemoController::class, 'typeError']);
    Route::get('/undefined-variable', [ErrorDemoController::class, 'undefinedVariable']);
    Route::get('/stack-overflow', [ErrorDemoController::class, 'stackOverflow']);
    Route::get('/json-decode-error', [ErrorDemoController::class, 'jsonDecodeError']);
    Route::get('/database-query-error', [ErrorDemoController::class, 'databaseQueryError']);
});

// 业务场景错误演示（用于 AI DevOps Copilot 价值展示）
Route::prefix('business-error')->group(function () {
    Route::get('/order-amount-precision',   [BusinessErrorController::class, 'orderAmountPrecisionError']);
    Route::get('/sql-injection',            [BusinessErrorController::class, 'sqlInjectionVulnerability']);
    Route::get('/payment-duplicate-charge', [BusinessErrorController::class, 'paymentDuplicateCharge']);
    Route::get('/external-api-no-timeout',  [BusinessErrorController::class, 'externalApiNoTimeout']);
    Route::get('/unauthorized-access',      [BusinessErrorController::class, 'unauthorizedDataAccess']);
    Route::get('/cache-stampede',           [BusinessErrorController::class, 'cacheStampede']);
    Route::get('/memory-exhaustion',        [BusinessErrorController::class, 'memoryExhaustionOnBulkExport']);
    Route::get('/n-plus-one-query',         [BusinessErrorController::class, 'nPlusOneQueryProblem']);
});

Route::get('/debug-sentry', function () {
    throw new Exception('My first Sentry error!');
});
