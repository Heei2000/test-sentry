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
$value = 0; // Default value when divisor is zero

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
