<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
        'level'
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log an activity
     */
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        string $level = 'info',
        ?User $user = null
    ): self {
        $user = $user ?: Auth::user();

        return self::create([
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'level' => $level
        ]);
    }

    /**
     * Log user authentication events
     */
    public static function logAuth(string $action, User $user, array $properties = []): self
    {
        $descriptions = [
            'login' => 'Người dùng đăng nhập thành công',
            'login_failed' => 'Đăng nhập thất bại',
            'logout' => 'Người dùng đăng xuất',
            'register' => 'Tài khoản mới được tạo',
            'password_reset' => 'Mật khẩu được đặt lại',
            'email_verified' => 'Email được xác thực',
            '2fa_enabled' => '2FA được kích hoạt',
            '2fa_disabled' => '2FA được tắt',
            '2fa_verified' => 'Xác thực 2FA thành công'
        ];

        $level = in_array($action, ['login_failed']) ? 'warning' : 'info';

        return self::log(
            $action,
            $descriptions[$action] ?? "Hoạt động xác thực: {$action}",
            $user,
            $properties,
            $level,
            $user
        );
    }

    /**
     * Log campaign activities
     */
    public static function logCampaign(string $action, Campaign $campaign, array $properties = []): self
    {
        $descriptions = [
            'created' => 'Tạo chiến dịch mới',
            'updated' => 'Cập nhật chiến dịch',
            'started' => 'Bắt đầu chiến dịch',
            'stopped' => 'Dừng chiến dịch',
            'completed' => 'Hoàn thành chiến dịch',
            'deleted' => 'Xóa chiến dịch',
            'shared' => 'Chia sẻ chiến dịch',
            'cloned' => 'Sao chép chiến dịch'
        ];

        return self::log(
            "campaign_{$action}",
            $descriptions[$action] ?? "Hoạt động chiến dịch: {$action}",
            $campaign,
            $properties
        );
    }

    /**
     * Log betting activities
     */
    public static function logBetting(string $action, array $properties = []): self
    {
        $descriptions = [
            'manual_bet' => 'Đặt cược thủ công',
            'auto_bet' => 'Đặt cược tự động',
            'bet_win' => 'Cược thắng',
            'bet_loss' => 'Cược thua',
            'strategy_changed' => 'Thay đổi chiến lược cược'
        ];

        return self::log(
            "betting_{$action}",
            $descriptions[$action] ?? "Hoạt động cược: {$action}",
            null,
            $properties
        );
    }

    /**
     * Log financial activities
     */
    public static function logFinancial(string $action, array $properties = []): self
    {
        $descriptions = [
            'deposit' => 'Nạp tiền vào ví',
            'withdraw' => 'Rút tiền từ ví',
            'transfer' => 'Chuyển tiền',
            'balance_adjustment' => 'Điều chỉnh số dư',
            'commission_earned' => 'Nhận hoa hồng',
            'fee_charged' => 'Tính phí giao dịch'
        ];

        $level = in_array($action, ['balance_adjustment']) ? 'warning' : 'info';

        return self::log(
            "financial_{$action}",
            $descriptions[$action] ?? "Hoạt động tài chính: {$action}",
            null,
            $properties,
            $level
        );
    }

    /**
     * Log security events
     */
    public static function logSecurity(string $action, array $properties = [], string $level = 'warning'): self
    {
        $descriptions = [
            'suspicious_login' => 'Đăng nhập đáng nghi',
            'multiple_failed_attempts' => 'Nhiều lần đăng nhập thất bại',
            'account_locked' => 'Tài khoản bị khóa',
            'account_unlocked' => 'Tài khoản được mở khóa',
            'permission_denied' => 'Truy cập bị từ chối',
            'unusual_activity' => 'Hoạt động bất thường'
        ];

        return self::log(
            "security_{$action}",
            $descriptions[$action] ?? "Sự kiện bảo mật: {$action}",
            null,
            $properties,
            $level
        );
    }

    /**
     * Get recent activities for user
     */
    public static function getRecentForUser(User $user, int $limit = 50)
    {
        return self::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities by action
     */
    public static function getByAction(string $action, int $limit = 100)
    {
        return self::where('action', $action)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clean old activity logs (keep last 6 months)
     */
    public static function cleanOldLogs(): int
    {
        return self::where('created_at', '<', now()->subMonths(6))->delete();
    }

    /**
     * Get activity statistics
     */
    public static function getStats(User $user = null, int $days = 30): array
    {
        $query = self::where('created_at', '>', now()->subDays($days));

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $stats = $query->selectRaw('
            action,
            level,
            COUNT(*) as count,
            DATE(created_at) as date
        ')
        ->groupBy('action', 'level', 'date')
        ->orderBy('date', 'desc')
        ->get();

        return [
            'total_activities' => $stats->sum('count'),
            'by_level' => $stats->groupBy('level')->map->sum('count'),
            'by_action' => $stats->groupBy('action')->map->sum('count'),
            'daily_breakdown' => $stats->groupBy('date')
        ];
    }
}
