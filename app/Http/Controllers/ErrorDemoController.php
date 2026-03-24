<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ErrorDemoController extends Controller
{
    /**
     * Throw a runtime exception to verify Sentry error reporting.
     */
    public function throwException(): Response
    {
        throw new \RuntimeException('Demo exception for Sentry');
    }

    /**
     * Trigger a DivisionByZeroError.
     */
    public function divideByZero(): Response
    {
// 演示用 - 故意触发除零错误
$value = 1 / 0; // Will throw DivisionByZeroError in PHP 8+

        return response("Result: {$value}");
    }

    /**
     * Capture a manual message without failing the request.
     */
    public function captureMessage(): Response
    {
        if (app()->bound('sentry')) {
            app('sentry')->captureMessage('Manual info message from demo route');
        } else {
            Log::info('Sentry container binding not found; message logged locally.');
        }

        return response('Manual message sent (or logged locally).');
    }
}
