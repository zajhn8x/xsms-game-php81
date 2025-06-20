<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'general'): Response
    {
        $limitConfig = $this->getRateLimitConfig($type);
        $key = $this->generateRateLimitKey($request, $type);

        // Check rate limit
        if (RateLimiter::tooManyAttempts($key, $limitConfig['max_attempts'])) {
            $this->logRateLimitExceeded($request, $type);
            return $this->rateLimitResponse($key);
        }

        // Increment attempt counter
        RateLimiter::hit($key, $limitConfig['decay_seconds']);

        $response = $next($request);

        // Add rate limit headers
        $this->addRateLimitHeaders($response, $key, $limitConfig);

        return $response;
    }

    /**
     * Get rate limit configuration for different types
     */
    private function getRateLimitConfig(string $type): array
    {
        return match($type) {
            'login' => [
                'max_attempts' => 5,
                'decay_seconds' => 900, // 15 minutes
                'message' => 'Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau 15 phút.'
            ],
            '2fa' => [
                'max_attempts' => 5,
                'decay_seconds' => 900, // 15 minutes
                'message' => 'Quá nhiều lần nhập mã 2FA sai. Vui lòng thử lại sau 15 phút.'
            ],
            'financial' => [
                'max_attempts' => 10,
                'decay_seconds' => 3600, // 1 hour
                'message' => 'Quá nhiều giao dịch tài chính. Vui lòng thử lại sau 1 giờ.'
            ],
            'api' => [
                'max_attempts' => 100,
                'decay_seconds' => 3600, // 1 hour
                'message' => 'Quá nhiều yêu cầu API. Vui lòng thử lại sau 1 giờ.'
            ],
            'campaign' => [
                'max_attempts' => 20,
                'decay_seconds' => 3600, // 1 hour
                'message' => 'Quá nhiều thao tác với chiến dịch. Vui lòng thử lại sau 1 giờ.'
            ],
            'general' => [
                'max_attempts' => 60,
                'decay_seconds' => 60, // 1 minute
                'message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau ít phút.'
            ],
            default => [
                'max_attempts' => 30,
                'decay_seconds' => 60,
                'message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau ít phút.'
            ]
        };
    }

    /**
     * Generate rate limit key
     */
    private function generateRateLimitKey(Request $request, string $type): string
    {
        $user = $request->user();
        $ip = $request->ip();

        // Use user ID if authenticated, otherwise use IP
        $identifier = $user ? "user:{$user->id}" : "ip:{$ip}";

        // Add route-specific identifier for certain types
        if (in_array($type, ['2fa', 'financial', 'campaign'])) {
            $route = $request->route()?->getName() ?? $request->path();
            return "rate_limit:{$type}:{$identifier}:{$route}";
        }

        return "rate_limit:{$type}:{$identifier}";
    }

    /**
     * Create rate limit exceeded response
     */
    private function rateLimitResponse(string $key): Response
    {
        $retryAfter = RateLimiter::availableIn($key);
        $config = $this->getRateLimitConfig('general');

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $config['message'],
                'retry_after' => $retryAfter,
                'retry_after_human' => $this->formatRetryAfter($retryAfter)
            ], 429);
        }

        return response()->view('errors.rate-limit', [
            'message' => $config['message'],
            'retry_after' => $retryAfter,
            'retry_after_human' => $this->formatRetryAfter($retryAfter)
        ], 429);
    }

    /**
     * Add rate limit headers to response
     */
    private function addRateLimitHeaders(Response $response, string $key, array $config): void
    {
        $maxAttempts = $config['max_attempts'];
        $remainingAttempts = RateLimiter::remaining($key, $maxAttempts);
        $retryAfter = RateLimiter::availableIn($key);

        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ]);

        if ($remainingAttempts === 0) {
            $response->headers->set('Retry-After', $retryAfter);
        }
    }

    /**
     * Log rate limit exceeded event
     */
    private function logRateLimitExceeded(Request $request, string $type): void
    {
        try {
            ActivityLog::logSecurity('rate_limit_exceeded', [
                'type' => $type,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id
            ], 'warning');
        } catch (\Exception $e) {
            // Don't break the request if logging fails
            Log::warning('Failed to log rate limit exceeded', [
                'error' => $e->getMessage(),
                'type' => $type,
                'ip' => $request->ip()
            ]);
        }
    }

    /**
     * Format retry after time to human readable
     */
    private function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} giây";
        } elseif ($seconds < 3600) {
            $minutes = ceil($seconds / 60);
            return "{$minutes} phút";
        } else {
            $hours = ceil($seconds / 3600);
            return "{$hours} giờ";
        }
    }

    /**
     * Clear rate limit for a specific key (useful for successful operations)
     */
    public static function clearRateLimit(string $key): void
    {
        RateLimiter::clear($key);
    }

    /**
     * Check if user/IP is currently rate limited
     */
    public static function isRateLimited(Request $request, string $type = 'general'): bool
    {
        $middleware = new self();
        $key = $middleware->generateRateLimitKey($request, $type);
        $config = $middleware->getRateLimitConfig($type);

        return RateLimiter::tooManyAttempts($key, $config['max_attempts']);
    }

    /**
     * Get remaining attempts for a request
     */
    public static function getRemainingAttempts(Request $request, string $type = 'general'): int
    {
        $middleware = new self();
        $key = $middleware->generateRateLimitKey($request, $type);
        $config = $middleware->getRateLimitConfig($type);

        return RateLimiter::remaining($key, $config['max_attempts']);
    }
}
