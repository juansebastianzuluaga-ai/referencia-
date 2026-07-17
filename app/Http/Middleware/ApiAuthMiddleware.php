<?php

namespace App\Http\Middleware;

use App\Models\ApiCredential;
use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $apiKey = $request->header('X-API-Key');
        $apiSecret = $request->header('X-API-Secret');

        if (! $apiKey || ! $apiSecret) {
            return $this->unauthorized('Missing API credentials in headers');
        }

        $credential = ApiCredential::where('api_key', $apiKey)->first();

        if (! $credential) {
            return $this->unauthorized('Invalid API Key');
        }

        if (! $credential->verifySecret($apiSecret)) {
            $this->logRequest($credential->id, $request, 401, $startTime, 'Invalid API Secret');

            return $this->unauthorized('Invalid API Secret');
        }

        if (! $credential->isActive()) {
            $this->logRequest($credential->id, $request, 401, $startTime, 'API credential is not active or has expired');

            return $this->unauthorized('API credential is not active or has expired');
        }

        if (! $credential->isIpAllowed($request->ip())) {
            $this->logRequest($credential->id, $request, 403, $startTime, 'IP address not allowed');

            return $this->forbidden('IP address not allowed');
        }

        $key = 'api-rate:'.$credential->id;
        if (RateLimiter::tooManyAttempts($key, $credential->rate_limit)) {
            $this->logRequest($credential->id, $request, 429, $startTime, 'Rate limit exceeded');

            return $this->rateLimitExceeded($credential->rate_limit);
        }

        RateLimiter::hit($key, 3600);

        $credential->markAsUsed();

        $request->attributes->set('api_credential', $credential);
        $request->attributes->set('api_request_start_time', $startTime);

        $response = $next($request);

        $this->logRequest(
            $credential->id,
            $request,
            $response->getStatusCode(),
            $startTime
        );

        return $response;
    }

    private function logRequest(int $credentialId, Request $request, int $statusCode, float $startTime, ?string $error = null): void
    {
        try {
            ApiRequestLog::create([
                'api_credential_id' => $credentialId,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $request->except(['password', 'api_secret']),
                'response_status' => $statusCode,
                'response_time' => (int) ((microtime(true) - $startTime) * 1000),
                'error_message' => $error,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log API request: '.$e->getMessage());
        }
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => new \stdClass,
        ], 401);
    }

    private function forbidden(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => new \stdClass,
        ], 403);
    }

    private function rateLimitExceeded(int $limit): Response
    {
        return response()->json([
            'success' => false,
            'message' => "Rate limit exceeded. Max {$limit} requests per hour.",
            'data' => new \stdClass,
        ], 429);
    }
}
