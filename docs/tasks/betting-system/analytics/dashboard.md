# Dashboard Tổng Quan

## Mục tiêu
Xây dựng dashboard tổng quan hiển thị các thống kê quan trọng, xu hướng và hiệu suất của hệ thống đặt cược cho người dùng và admin.

## Prerequisites
- User authentication đã hoạt động
- Campaign và Wallet system đã được triển khai
- Chart.js hoặc thư viện visualization đã được cài đặt

## Dashboard Components

### 1. User Dashboard
- **Portfolio Overview**: Tổng quan danh mục đầu tư
- **Active Campaigns**: Chiến dịch đang chạy
- **Performance Metrics**: Các chỉ số hiệu suất
- **Recent Activities**: Hoạt động gần đây
- **Profit/Loss Charts**: Biểu đồ lãi/lỗ

### 2. Admin Dashboard  
- **System Overview**: Tổng quan hệ thống
- **User Analytics**: Phân tích người dùng
- **Financial Metrics**: Các chỉ số tài chính
- **Campaign Statistics**: Thống kê chiến dịch
- **System Health**: Tình trạng hệ thống

## Các Bước Thực Hiện

### Bước 1: Cài đặt Chart.js
```bash
npm install chart.js
npm install date-fns # for date manipulation
```

### Bước 2: Tạo Dashboard Service
```php
// app/Services/DashboardService.php
class DashboardService
{
    public function getUserDashboardData($userId)
    {
        $user = User::findOrFail($userId);
        $wallet = app(WalletService::class)->getOrCreateWallet($userId);
        
        return [
            'wallet_summary' => $this->getWalletSummary($wallet),
            'campaign_overview' => $this->getCampaignOverview($userId),
            'performance_metrics' => $this->getPerformanceMetrics($userId),
            'recent_activities' => $this->getRecentActivities($userId),
            'profit_loss_chart' => $this->getProfitLossChart($userId),
            'win_rate_chart' => $this->getWinRateChart($userId)
        ];
    }

    public function getAdminDashboardData()
    {
        return [
            'system_overview' => $this->getSystemOverview(),
            'user_analytics' => $this->getUserAnalytics(),
            'financial_metrics' => $this->getFinancialMetrics(),
            'campaign_statistics' => $this->getCampaignStatistics(),
            'top_performers' => $this->getTopPerformers(),
            'recent_activities' => $this->getSystemActivities()
        ];
    }

    private function getWalletSummary($wallet)
    {
        return [
            'total_balance' => $wallet->total_balance,
            'available_balance' => $wallet->available_balance,
            'real_balance' => $wallet->real_balance,
            'virtual_balance' => $wallet->virtual_balance,
            'bonus_balance' => $wallet->bonus_balance,
            'frozen_balance' => $wallet->frozen_balance,
            'total_deposited' => $wallet->total_deposited,
            'total_withdrawn' => $wallet->total_withdrawn,
            'net_deposit' => $wallet->total_deposited - $wallet->total_withdrawn
        ];
    }

    private function getCampaignOverview($userId)
    {
        $campaigns = Campaign::where('user_id', $userId);
        
        return [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->whereIn('status', ['active', 'running'])->count(),
            'completed_campaigns' => $campaigns->where('status', 'completed')->count(),
            'total_invested' => $campaigns->sum('initial_balance'),
            'current_value' => $campaigns->sum('current_balance'),
            'total_profit' => $campaigns->sum(DB::raw('current_balance - initial_balance')),
            'avg_profit_rate' => $this->calculateAverageProfitRate($userId),
            'best_campaign' => $this->getBestCampaign($userId),
            'worst_campaign' => $this->getWorstCampaign($userId)
        ];
    }

    private function getPerformanceMetrics($userId)
    {
        $campaigns = Campaign::where('user_id', $userId)->get();
        $totalBets = CampaignBet::whereIn('campaign_id', $campaigns->pluck('id'))->count();
        $winBets = CampaignBet::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('is_win', true)->count();
        
        $dailyProfits = $this->getDailyProfits($userId);
        $weeklyProfits = $this->getWeeklyProfits($userId);
        $monthlyProfits = $this->getMonthlyProfits($userId);

        return [
            'total_bets' => $totalBets,
            'win_bets' => $winBets,
            'win_rate' => $totalBets > 0 ? round(($winBets / $totalBets) * 100, 2) : 0,
            'avg_bet_amount' => $totalBets > 0 ? 
                CampaignBet::whereIn('campaign_id', $campaigns->pluck('id'))->avg('amount') : 0,
            'daily_profit' => $dailyProfits,
            'weekly_profit' => $weeklyProfits,
            'monthly_profit' => $monthlyProfits,
            'total_days_active' => $this->getTotalDaysActive($userId),
            'current_streak' => $this->getCurrentStreak($userId),
            'best_streak' => $this->getBestStreak($userId)
        ];
    }

    private function getRecentActivities($userId, $limit = 10)
    {
        $activities = collect();
        
        // Recent campaigns
        $recentCampaigns = Campaign::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($campaign) {
                return [
                    'type' => 'campaign_created',
                    'title' => "Tạo chiến dịch: {$campaign->name}",
                    'created_at' => $campaign->created_at,
                    'data' => $campaign
                ];
            });

        // Recent bets
        $recentBets = CampaignBet::whereIn('campaign_id', 
                Campaign::where('user_id', $userId)->pluck('id')
            )
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->with('campaign')
            ->get()
            ->map(function($bet) {
                return [
                    'type' => 'bet_placed',
                    'title' => "Đặt cược {$bet->lo_number} - {$bet->campaign->name}",
                    'created_at' => $bet->created_at,
                    'data' => $bet
                ];
            });

        // Recent transactions
        $wallet = app(WalletService::class)->getOrCreateWallet($userId);
        $recentTransactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($transaction) {
                return [
                    'type' => 'transaction',
                    'title' => "Giao dịch: " . ucfirst($transaction->type),
                    'created_at' => $transaction->created_at,
                    'data' => $transaction
                ];
            });

        return $activities
            ->merge($recentCampaigns)
            ->merge($recentBets)
            ->merge($recentTransactions)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }

    private function getProfitLossChart($userId, $days = 30)
    {
        $startDate = now()->subDays($days);
        
        $dailyData = CampaignBet::whereIn('campaign_id', 
                Campaign::where('user_id', $userId)->pluck('id')
            )
            ->where('bet_date', '>=', $startDate)
            ->selectRaw('DATE(bet_date) as date, SUM(CASE WHEN is_win THEN win_amount - amount ELSE -amount END) as profit')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $profits = [];
        $cumulativeProfit = 0;
        $cumulativeProfits = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d/m');
            
            $dayData = $dailyData->firstWhere('date', $date);
            $dayProfit = $dayData ? $dayData->profit : 0;
            
            $profits[] = $dayProfit;
            $cumulativeProfit += $dayProfit;
            $cumulativeProfits[] = $cumulativeProfit;
        }

        return [
            'labels' => $labels,
            'daily_profits' => $profits,
            'cumulative_profits' => $cumulativeProfits
        ];
    }

    private function getWinRateChart($userId, $days = 30)
    {
        $startDate = now()->subDays($days);
        
        $dailyWinRates = CampaignBet::whereIn('campaign_id', 
                Campaign::where('user_id', $userId)->pluck('id')
            )
            ->where('bet_date', '>=', $startDate)
            ->selectRaw('
                DATE(bet_date) as date, 
                COUNT(*) as total_bets,
                SUM(CASE WHEN is_win THEN 1 ELSE 0 END) as win_bets
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $winRates = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d/m');
            
            $dayData = $dailyWinRates->firstWhere('date', $date);
            $winRate = $dayData && $dayData->total_bets > 0 
                ? round(($dayData->win_bets / $dayData->total_bets) * 100, 2) 
                : 0;
            
            $winRates[] = $winRate;
        }

        return [
            'labels' => $labels,
            'win_rates' => $winRates
        ];
    }

    // Admin Dashboard Methods
    private function getSystemOverview()
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::whereIn('status', ['active', 'running'])->count(),
            'total_bets' => CampaignBet::count(),
            'total_bet_amount' => CampaignBet::sum('amount'),
            'total_winnings' => CampaignBet::where('is_win', true)->sum('win_amount'),
            'platform_revenue' => $this->calculatePlatformRevenue()
        ];
    }

    private function getUserAnalytics()
    {
        $usersByRole = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->selectRaw('roles.name as role, COUNT(*) as count')
            ->groupBy('roles.name')
            ->get()
            ->pluck('count', 'role')
            ->toArray();

        $userRegistrations = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'users_by_role' => $usersByRole,
            'user_registrations' => $userRegistrations,
            'retention_rate' => $this->calculateRetentionRate(),
            'avg_session_duration' => $this->calculateAverageSessionDuration()
        ];
    }

    private function getFinancialMetrics()
    {
        $totalDeposits = WalletTransaction::where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');
            
        $totalWithdrawals = WalletTransaction::where('type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('amount');

        $monthlyDeposits = WalletTransaction::where('type', 'deposit')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $monthlyWithdrawals = WalletTransaction::where('type', 'withdrawal')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        return [
            'total_deposits' => $totalDeposits,
            'total_withdrawals' => $totalWithdrawals,
            'net_inflow' => $totalDeposits - $totalWithdrawals,
            'monthly_deposits' => $monthlyDeposits,
            'monthly_withdrawals' => $monthlyWithdrawals,
            'monthly_net_inflow' => $monthlyDeposits - $monthlyWithdrawals,
            'avg_deposit_amount' => WalletTransaction::where('type', 'deposit')
                ->where('status', 'completed')
                ->avg('amount'),
            'avg_withdrawal_amount' => WalletTransaction::where('type', 'withdrawal')
                ->where('status', 'completed')
                ->avg('amount')
        ];
    }

    private function getCampaignStatistics()
    {
        $campaignsByStrategy = Campaign::selectRaw('betting_strategy, COUNT(*) as count')
            ->groupBy('betting_strategy')
            ->get()
            ->pluck('count', 'betting_strategy')
            ->toArray();

        $campaignsByStatus = Campaign::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'campaigns_by_strategy' => $campaignsByStrategy,
            'campaigns_by_status' => $campaignsByStatus,
            'avg_campaign_duration' => $this->calculateAverageCampaignDuration(),
            'avg_campaign_profit_rate' => $this->calculateAverageCampaignProfitRate()
        ];
    }

    private function getTopPerformers($limit = 10)
    {
        return User::with('campaigns')
            ->get()
            ->map(function($user) {
                $totalProfit = $user->campaigns->sum(function($campaign) {
                    return $campaign->current_balance - $campaign->initial_balance;
                });
                
                return [
                    'user' => $user,
                    'total_profit' => $totalProfit,
                    'profit_rate' => $user->campaigns->sum('initial_balance') > 0 
                        ? ($totalProfit / $user->campaigns->sum('initial_balance')) * 100 
                        : 0,
                    'total_campaigns' => $user->campaigns->count()
                ];
            })
            ->sortByDesc('total_profit')
            ->take($limit)
            ->values();
    }

    // Helper methods
    private function calculateAverageProfitRate($userId)
    {
        $campaigns = Campaign::where('user_id', $userId)
            ->where('initial_balance', '>', 0)
            ->get();

        if ($campaigns->isEmpty()) return 0;

        $totalProfitRate = $campaigns->sum(function($campaign) {
            return (($campaign->current_balance - $campaign->initial_balance) / $campaign->initial_balance) * 100;
        });

        return round($totalProfitRate / $campaigns->count(), 2);
    }

    private function getBestCampaign($userId)
    {
        return Campaign::where('user_id', $userId)
            ->selectRaw('*, (current_balance - initial_balance) as profit')
            ->orderBy('profit', 'desc')
            ->first();
    }

    private function getWorstCampaign($userId)
    {
        return Campaign::where('user_id', $userId)
            ->selectRaw('*, (current_balance - initial_balance) as profit')
            ->orderBy('profit', 'asc')
            ->first();
    }

    private function getDailyProfits($userId)
    {
        return CampaignBet::whereIn('campaign_id', 
                Campaign::where('user_id', $userId)->pluck('id')
            )
            ->whereDate('bet_date', today())
            ->sum(DB::raw('CASE WHEN is_win THEN win_amount - amount ELSE -amount END'));
    }

    private function getWeeklyProfits($userId)
    {
        return CampaignBet::whereIn('campaign_id', 
                Campaign::where('user_id', $userId)->pluck('id')
            )
            ->where('bet_date', '>=', now()->startOfWeek())
            ->sum(DB::raw('CASE WHEN is_win THEN win_amount - amount ELSE -amount END'));
    }

    private function getMonthlyProfits($userId)
    {
        return CampaignBet::whereIn('campaign_id', 
                Campaign::where('user_id', $userId)->pluck('id')
            )
            ->whereMonth('bet_date', now()->month)
            ->sum(DB::raw('CASE WHEN is_win THEN win_amount - amount ELSE -amount END'));
    }

    private function calculatePlatformRevenue()
    {
        // Platform revenue calculation logic
        // This could be commission from transactions, subscription fees, etc.
        return 0;
    }

    private function calculateRetentionRate()
    {
        $usersLastMonth = User::where('created_at', '<=', now()->subMonth())->count();
        $activeUsersThisMonth = User::where('created_at', '<=', now()->subMonth())
            ->where('last_login_at', '>=', now()->subMonth())
            ->count();

        return $usersLastMonth > 0 ? round(($activeUsersThisMonth / $usersLastMonth) * 100, 2) : 0;
    }

    private function calculateAverageSessionDuration()
    {
        // This would require session tracking implementation
        return 0;
    }

    private function calculateAverageCampaignDuration()
    {
        return Campaign::whereNotNull('end_date')
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_duration')
            ->value('avg_duration') ?? 0;
    }

    private function calculateAverageCampaignProfitRate()
    {
        return Campaign::where('initial_balance', '>', 0)
            ->selectRaw('AVG((current_balance - initial_balance) / initial_balance * 100) as avg_profit_rate')
            ->value('avg_profit_rate') ?? 0;
    }
}
```

### Bước 3: Tạo Dashboard Controller
```php
// app/Http/Controllers/DashboardController.php
class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
        $this->middleware('auth');
    }

    public function index()
    {
        $data = $this->dashboardService->getUserDashboardData(auth()->id());
        
        return view('dashboard.index', $data);
    }

    public function admin()
    {
        $this->authorize('view-admin-dashboard');
        
        $data = $this->dashboardService->getAdminDashboardData();
        
        return view('dashboard.admin', $data);
    }

    public function getChartData(Request $request)
    {
        $type = $request->get('type');
        $days = $request->get('days', 30);
        
        switch ($type) {
            case 'profit-loss':
                return response()->json(
                    $this->dashboardService->getProfitLossChart(auth()->id(), $days)
                );
            case 'win-rate':
                return response()->json(
                    $this->dashboardService->getWinRateChart(auth()->id(), $days)
                );
            default:
                return response()->json(['error' => 'Invalid chart type'], 400);
        }
    }
}
```

### Bước 4: Tạo Dashboard View
```blade
{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Dashboard</h1>

    {{-- Wallet Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tổng số dư
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($wallet_summary['total_balance']) }}đ
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tổng đầu tư
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($campaign_overview['total_invested']) }}đ
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-{{ $campaign_overview['total_profit'] >= 0 ? 'success' : 'danger' }}">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-{{ $campaign_overview['total_profit'] >= 0 ? 'success' : 'danger' }} text-uppercase mb-1">
                                Lợi nhuận
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $campaign_overview['total_profit'] >= 0 ? '+' : '' }}{{ number_format($campaign_overview['total_profit']) }}đ
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-{{ $campaign_overview['total_profit'] >= 0 ? 'arrow-up' : 'arrow-down' }} fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Tỷ lệ thắng
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $performance_metrics['win_rate'] }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Charts Column --}}
        <div class="col-lg-8">
            {{-- Profit/Loss Chart --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Biểu đồ Lãi/Lỗ</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary chart-period" data-days="7">7 ngày</button>
                        <button type="button" class="btn btn-primary chart-period" data-days="30">30 ngày</button>
                        <button type="button" class="btn btn-outline-primary chart-period" data-days="90">90 ngày</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="profitLossChart" height="100"></canvas>
                </div>
            </div>

            {{-- Win Rate Chart --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Tỷ lệ thắng theo ngày</h5>
                </div>
                <div class="card-body">
                    <canvas id="winRateChart" height="100"></canvas>
                </div>
            </div>
        </div>

        {{-- Sidebar Column --}}
        <div class="col-lg-4">
            {{-- Performance Metrics --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Hiệu suất</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <div class="text-sm text-muted">Hôm nay</div>
                            <div class="font-weight-bold text-{{ $performance_metrics['daily_profit'] >= 0 ? 'success' : 'danger' }}">
                                {{ $performance_metrics['daily_profit'] >= 0 ? '+' : '' }}{{ number_format($performance_metrics['daily_profit']) }}đ
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm text-muted">Tuần này</div>
                            <div class="font-weight-bold text-{{ $performance_metrics['weekly_profit'] >= 0 ? 'success' : 'danger' }}">
                                {{ $performance_metrics['weekly_profit'] >= 0 ? '+' : '' }}{{ number_format($performance_metrics['weekly_profit']) }}đ
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <div class="text-sm text-muted">Tháng này</div>
                            <div class="font-weight-bold text-{{ $performance_metrics['monthly_profit'] >= 0 ? 'success' : 'danger' }}">
                                {{ $performance_metrics['monthly_profit'] >= 0 ? '+' : '' }}{{ number_format($performance_metrics['monthly_profit']) }}đ
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm text-muted">Tỷ lệ lãi TB</div>
                            <div class="font-weight-bold">
                                {{ $campaign_overview['avg_profit_rate'] }}%
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="text-sm text-muted">Tổng cược</div>
                            <div class="font-weight-bold">{{ $performance_metrics['total_bets'] }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm text-muted">Cược thắng</div>
                            <div class="font-weight-bold text-success">{{ $performance_metrics['win_bets'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Campaigns --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Chiến dịch đang chạy</h5>
                    <a href="{{ route('campaigns.index') }}" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                </div>
                <div class="card-body">
                    @if($campaign_overview['active_campaigns'] > 0)
                        @php
                            $activeCampaigns = \App\Models\Campaign::where('user_id', auth()->id())
                                ->whereIn('status', ['active', 'running'])
                                ->latest()
                                ->take(3)
                                ->get();
                        @endphp
                        
                        @foreach($activeCampaigns as $campaign)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <div class="font-weight-bold">{{ $campaign->name }}</div>
                                <small class="text-muted">{{ $campaign->betting_strategy }}</small>
                            </div>
                            <div class="text-right">
                                <div class="font-weight-bold text-{{ $campaign->profit >= 0 ? 'success' : 'danger' }}">
                                    {{ $campaign->profit >= 0 ? '+' : '' }}{{ number_format($campaign->profit) }}đ
                                </div>
                                <small class="text-muted">{{ $campaign->profit_percentage }}%</small>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                        @endforeach
                    @else
                        <p class="text-center text-muted">Không có chiến dịch nào đang chạy</p>
                        <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm w-100">
                            Tạo chiến dịch mới
                        </a>
                    @endif
                </div>
            </div>

            {{-- Recent Activities --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Hoạt động gần đây</h5>
                </div>
                <div class="card-body">
                    @foreach($recent_activities->take(5) as $activity)
                    <div class="d-flex align-items-center mb-2">
                        <div class="activity-icon mr-2">
                            @switch($activity['type'])
                                @case('campaign_created')
                                    <i class="fas fa-plus-circle text-success"></i>
                                    @break
                                @case('bet_placed')
                                    <i class="fas fa-dice text-warning"></i>
                                    @break
                                @case('transaction')
                                    <i class="fas fa-exchange-alt text-info"></i>
                                    @break
                                @default
                                    <i class="fas fa-circle text-secondary"></i>
                            @endswitch
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-sm">{{ $activity['title'] }}</div>
                            <small class="text-muted">{{ $activity['created_at']->diffForHumans() }}</small>
                        </div>
                    </div>
                    @if(!$loop->last)<hr>@endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
.border-left-danger {
    border-left: 4px solid #e74a3b !important;
}
.card {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
    border: 1px solid #e3e6f0;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let profitLossChart;
    let winRateChart;

    // Initialize charts
    initializeProfitLossChart();
    initializeWinRateChart();

    // Chart period buttons
    document.querySelectorAll('.chart-period').forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active button
            document.querySelectorAll('.chart-period').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');

            // Update chart
            const days = this.dataset.days;
            updateProfitLossChart(days);
        });
    });

    function initializeProfitLossChart() {
        const ctx = document.getElementById('profitLossChart').getContext('2d');
        const chartData = @json($profit_loss_chart);

        profitLossChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Lãi/Lỗ hàng ngày',
                    data: chartData.daily_profits,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Lãi/Lỗ tích lũy',
                    data: chartData.cumulative_profits,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + 
                                    new Intl.NumberFormat('vi-VN').format(context.parsed.y) + 'đ';
                            }
                        }
                    }
                }
            }
        });
    }

    function initializeWinRateChart() {
        const ctx = document.getElementById('winRateChart').getContext('2d');
        const chartData = @json($win_rate_chart);

        winRateChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Tỷ lệ thắng (%)',
                    data: chartData.win_rates,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Tỷ lệ thắng: ' + context.parsed.y + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    function updateProfitLossChart(days) {
        fetch(`/dashboard/chart-data?type=profit-loss&days=${days}`)
            .then(response => response.json())
            .then(data => {
                profitLossChart.data.labels = data.labels;
                profitLossChart.data.datasets[0].data = data.daily_profits;
                profitLossChart.data.datasets[1].data = data.cumulative_profits;
                profitLossChart.update();
            });
    }
});
</script>
@endsection
```

### Bước 5: Tạo Routes
```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
    
    Route::middleware('permission:manage_system')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
    });
});
```

## Testing

### Feature Tests
```php
// tests/Feature/DashboardTest.php
class DashboardTest extends TestCase
{
    public function test_authenticated_user_can_view_dashboard()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
    }

    public function test_dashboard_displays_correct_data()
    {
        $user = User::factory()->create();
        
        // Create some test data
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 1000000,
            'current_balance' => 1100000
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        
        $response->assertSee('100,000'); // Profit display
    }
}
```

## Performance Optimization

1. **Caching**: Cache dashboard data với TTL phù hợp
2. **Database Indexing**: Index các cột thường query
3. **Eager Loading**: Load relationships để tránh N+1 queries
4. **Chart Data Caching**: Cache chart data để tránh tính toán lại

## Real-time Updates

1. **WebSocket Integration**: Cập nhật real-time cho active campaigns
2. **Server-Sent Events**: Push notifications cho dashboard
3. **AJAX Polling**: Định kỳ update một số metrics quan trọng

Các task này tạo thành một hệ thống đặt cược hoàn chỉnh với khả năng quản lý nhiều người dùng, đặt cược thử nghiệm với dữ liệu quá khứ, và dashboard analytics chi tiết. 
