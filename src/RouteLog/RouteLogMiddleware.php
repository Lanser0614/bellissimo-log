<?php

namespace Bellissimopizza\BellissimoLog\RouteLog;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class RouteLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Get unique identifier for this request-response cycle
        $requestId = $request->attributes->get('X-Request-ID');

        // Log the incoming request
        $startTime = microtime(true);

        Log::debug('Request', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
//            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'timestamp' => now()->toIso8601String()
        ]);

        try {
            // Process the request
            /** @var Response $response */
            $response = $next($request);

            // Calculate execution time
            $executionTime = microtime(true) - $startTime;

            json_decode($response->content(), true);

            $responseContent = (json_last_error() != JSON_ERROR_NONE) ? $response->content() : json_decode($response->content(), true);

            // Log the successful response
            Log::debug('Response', [
                'request_id' => $requestId,
                'status' => $response->status(),
                'execution_time' => round($executionTime, 4) . 's',
                'timestamp' => now()->toIso8601String(),
                'response' => $responseContent
            ]);


            return $response;

        } catch (Throwable $exception) {
            // Calculate execution time until exception
            $executionTime = microtime(true) - $startTime;

            // Log the exception
            Log::debug('Exception', [
                'request_id' => $requestId,
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'execution_time' => round($executionTime, 4) . 's',
                'timestamp' => now()->toIso8601String()
            ]);

            throw $exception; // Re-throw the exception for Laravel's exception handler
        }
    }
}