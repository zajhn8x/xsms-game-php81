<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Process the request first
        $response = $next($request);

        // Only log for authenticated users
        if (Auth::check() && $this->shouldLog($request)) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    /**
     * Determine if we should log this request
     */
    private function shouldLog(Request $request): bool
    {
        // Don't log certain routes
        $excludeRoutes = [
            'api/user',
            'sanctum/csrf-cookie',
            'livewire/update',
            'telescope/*',
            '_debugbar/*'
        ];

        $path = $request->path();

        foreach ($excludeRoutes as $route) {
            if (fnmatch($route, $path)) {
                return false;
            }
        }

        // Don't log GET requests to assets or API polling endpoints
        if ($request->method() === 'GET') {
            $skipGetRoutes = [
                'api/*',
                '*.js',
                '*.css',
                '*.ico',
                '*.png',
                '*.jpg',
                '*.svg'
            ];

            foreach ($skipGetRoutes as $route) {
                if (fnmatch($route, $path)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Log the activity
     */
    private function logActivity(Request $request, Response $response): void
    {
        try {
            $user = Auth::user();
            $method = $request->method();
            $path = $request->path();
            $routeName = $request->route()?->getName();
            $statusCode = $response->getStatusCode();

            // Determine action and description based on route and method
            [$action, $description] = $this->determineActionAndDescription(
                $method,
                $path,
                $routeName,
                $statusCode
            );

            // Prepare properties
            $properties = [
                'method' => $method,
                'path' => $path,
                'route_name' => $routeName,
                'status_code' => $statusCode,
                'request_data' => $this->filterSensitiveData($request->all())
            ];

            // Determine log level based on status code
            $level = $this->determineLogLevel($statusCode);

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'description' => $description,
                'properties' => $properties,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'level' => $level
            ]);

        } catch (\Exception $e) {
            // Don't break the application if logging fails
            Log::error('Failed to log activity', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'path' => $request->path()
            ]);
        }
    }

    /**
     * Determine action and description from request
     */
    private function determineActionAndDescription(
        string $method,
        string $path,
        ?string $routeName,
        int $statusCode
    ): array {

        // Map common patterns
        $patterns = [
            // Authentication
            'login' => ['page_view', 'Truy cập trang đăng nhập'],
            'register' => ['page_view', 'Truy cập trang đăng ký'],
            'logout' => ['logout', 'Đăng xuất'],

            // Campaign management
            'campaigns' => [
                'GET' => ['campaign_view', 'Xem danh sách chiến dịch'],
                'POST' => ['campaign_create', 'Tạo chiến dịch mới'],
                'PUT' => ['campaign_update', 'Cập nhật chiến dịch'],
                'PATCH' => ['campaign_update', 'Cập nhật chiến dịch'],
                'DELETE' => ['campaign_delete', 'Xóa chiến dịch']
            ],

            // Wallet management
            'wallet' => [
                'GET' => ['wallet_view', 'Xem thông tin ví'],
                'POST' => ['wallet_action', 'Thực hiện giao dịch ví']
            ],

            // Two-factor authentication
            'two-factor' => [
                'GET' => ['2fa_view', 'Xem cài đặt 2FA'],
                'POST' => ['2fa_action', 'Thực hiện hành động 2FA'],
                'DELETE' => ['2fa_disable', 'Tắt 2FA']
            ],

            // Dashboard
            'dashboard' => ['dashboard_view', 'Truy cập dashboard'],

            // Betting
            'bet' => [
                'GET' => ['betting_view', 'Xem thông tin cược'],
                'POST' => ['betting_place', 'Đặt cược']
            ],

            // Historical testing
            'historical-testing' => [
                'GET' => ['historical_view', 'Xem kiểm thử lịch sử'],
                'POST' => ['historical_run', 'Chạy kiểm thử lịch sử']
            ],

            // Social features
            'social' => [
                'GET' => ['social_view', 'Xem tính năng xã hội'],
                'POST' => ['social_action', 'Thực hiện hành động xã hội']
            ]
        ];

        // Check specific route patterns
        foreach ($patterns as $pattern => $config) {
            if (str_contains($path, $pattern)) {
                if (is_array($config) && isset($config[$method])) {
                    return $config[$method];
                } elseif (is_array($config) && count($config) === 2) {
                    return $config;
                }
            }
        }

        // Default based on method and status
        $action = strtolower($method) . '_request';
        $description = $this->getDefaultDescription($method, $path, $statusCode);

        return [$action, $description];
    }

    /**
     * Get default description
     */
    private function getDefaultDescription(string $method, string $path, int $statusCode): string
    {
        $methodMap = [
            'GET' => 'Truy cập',
            'POST' => 'Gửi dữ liệu đến',
            'PUT' => 'Cập nhật',
            'PATCH' => 'Cập nhật một phần',
            'DELETE' => 'Xóa'
        ];

        $statusText = $statusCode >= 400 ? ' (Lỗi)' : '';

        return ($methodMap[$method] ?? $method) . " {$path}{$statusText}";
    }

    /**
     * Determine log level based on status code
     */
    private function determineLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } else {
            return 'info';
        }
    }

    /**
     * Filter sensitive data from request
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitive = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'secret',
            'two_factor_secret',
            'recovery_code',
            'credit_card',
            'cvv',
            'ssn'
        ];

        foreach ($sensitive as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }

        return $data;
    }
}
