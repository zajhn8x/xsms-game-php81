<?php

namespace App\Services;

use App\Models\User;
use App\Models\Campaign;
use App\Models\CampaignBet;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardService
{
    public function getUserDashboard($userId)
    {
        $user = User::findOrFail($userId);
        $wallet = $user->wallet;

        return [
            'wallet_summary' => $this->getUserWalletSummary($user),
            'campaign_overview' => $this->getUserCampaignOverview($user),
            'performance_metrics' => $this->getUserPerformanceMetrics($user),
            'recent_activities' => $this->getUserRecentActivities($user),
            'charts_data' => $this->getUserChartsData($user)
        ];
    }

    public function getAdminDashboard()
    {
        return Cache::remember('admin_dashboard', 300, function () {
            return [
                'system_overview' => $this->getSystemOverview(),
                'user_analytics' => $this->getUserAnalytics(),
                'financial_metrics' => $this->getFinancialMetrics(),
                'campaign_statistics' => $this->getCampaignStatistics(),
                'system_charts' => $this->getSystemChartsData()
            ];
        });
    }

    private function getUserWalletSummary($user)
    {
        $wallet = $user->wallet ?? $user->wallet()->create([
            'real_balance' => 0,
            'virtual_balance' => 1000000,
            'frozen_balance' => 0,
            'bonus_balance' => 0
        ]);

        return [
            'real_balance' => $wallet->real_balance,
            'virtual_balance' => $wallet->virtual_balance,
            'bonus_balance' => $wallet->bonus_balance,
            'total_balance' => $wallet->total_balance,
            'usable_balance' => $wallet->usable_balance,
            'total_deposited' => $wallet->total_deposited,
            'total_withdrawn' => $wallet->total_withdrawn,
            'last_transaction_at' => $wallet->last_transaction_at
        ];
    }

    private function getUserCampaignOverview($user)
    {
        $campaigns = $user->campaigns;

        return [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->whereIn('status', ['active', 'running'])->count(),
            'completed_campaigns' => $campaigns->where('status', 'completed')->count(),
            'paused_campaigns' => $campaigns->where('status', 'paused')->count(),
            'public_campaigns' => $campaigns->where('is_public', true)->count()
        ];
    }

    private function getUserPerformanceMetrics($user)
    {
        $campaigns = $user->campaigns;
        $totalInitialBalance = $campaigns->sum('initial_balance');
        $totalCurrentBalance = $campaigns->sum('current_balance');
        $totalProfit = $totalCurrentBalance - $totalInitialBalance;

        $totalBets = $campaigns->sum('total_bet_count');
        $totalWinBets = $campaigns->sum('win_bet_count');
        $averageWinRate = $totalBets > 0 ? round(($totalWinBets / $totalBets) * 100, 2) : 0;

        return [
            'total_invested' => $totalInitialBalance,
            'current_value' => $totalCurrentBalance,
            'total_profit' => $totalProfit,
            'profit_percentage' => $totalInitialBalance > 0 ? round(($totalProfit / $totalInitialBalance) * 100, 2) : 0,
            'total_bets' => $totalBets,
            'win_rate' => $averageWinRate,
            'best_campaign' => $this->getBestCampaign($campaigns),
            'worst_campaign' => $this->getWorstCampaign($campaigns)
        ];
    }

    private function getUserRecentActivities($user)
    {
        $recentTransactions = $user->wallet ?
            $user->wallet->transactions()->orderBy('created_at', 'desc')->limit(10)->get() :
            collect();

        $recentBets = CampaignBet::whereHas('campaign', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->orderBy('created_at', 'desc')->limit(10)->get();

        return [
            'recent_transactions' => $recentTransactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at
                ];
            }),
            'recent_bets' => $recentBets->map(function ($bet) {
                return [
                    'id' => $bet->id,
                    'campaign_name' => $bet->campaign->name ?? 'N/A',
                    'amount' => $bet->amount,
                    'is_win' => $bet->is_win,
                    'profit' => $bet->profit,
                    'created_at' => $bet->created_at
                ];
            })
        ];
    }

    private function getUserChartsData($user)
    {
        $campaigns = $user->campaigns;
        $dailyData = $this->getDailyProfitData($campaigns);
        $userActivity = $this->getUserActivityData();

        return [
            'profit_loss_chart' => $dailyData,
            'win_rate_chart' => $this->getWinRateData($campaigns),
            'campaign_performance' => [
                'labels' => $campaigns->pluck('name')->toArray(),
                'data' => $campaigns->map(function($campaign) {
                    return $campaign->current_balance - $campaign->initial_balance;
                })->toArray()
            ],
            'user_activity' => [
                'labels' => array_column($userActivity, 'date'),
                'data' => array_column($userActivity, 'activity')
            ]
        ];
    }

    private function getSystemOverview()
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'premium_users' => User::where('subscription_type', 'premium')->count(),
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::whereIn('status', ['active', 'running'])->count(),
            'total_bets_today' => CampaignBet::whereDate('created_at', today())->count(),
            'total_profit_today' => $this->getTotalProfitToday()
        ];
    }

    private function getUserAnalytics()
    {
        $last30Days = Carbon::now()->subDays(30);

        return [
            'new_users_30_days' => User::where('created_at', '>=', $last30Days)->count(),
            'active_users_30_days' => User::where('last_login_at', '>=', $last30Days)->count(),
            'user_growth_chart' => $this->getUserGrowthData(),
            'subscription_distribution' => $this->getSubscriptionDistribution()
        ];
    }

    private function getFinancialMetrics()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'total_deposits' => WalletTransaction::where('type', 'deposit')
                ->where('status', 'completed')->sum('amount'),
            'total_withdrawals' => WalletTransaction::where('type', 'withdrawal')
                ->where('status', 'completed')->sum('amount'),
            'deposits_today' => WalletTransaction::where('type', 'deposit')
                ->whereDate('created_at', $today)
                ->where('status', 'completed')->sum('amount'),
            'withdrawals_today' => WalletTransaction::where('type', 'withdrawal')
                ->whereDate('created_at', $today)
                ->where('status', 'completed')->sum('amount'),
            'deposits_this_month' => WalletTransaction::where('type', 'deposit')
                ->where('created_at', '>=', $thisMonth)
                ->where('status', 'completed')->sum('amount'),
            'withdrawals_this_month' => WalletTransaction::where('type', 'withdrawal')
                ->where('created_at', '>=', $thisMonth)
                ->where('status', 'completed')->sum('amount')
        ];
    }

    private function getCampaignStatistics()
    {
        return [
            'campaigns_by_status' => Campaign::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')->get(),
            'campaigns_by_strategy' => Campaign::select('betting_strategy', DB::raw('count(*) as count'))
                ->groupBy('betting_strategy')->get(),
            'avg_campaign_duration' => Campaign::where('status', 'completed')
                ->avg(DB::raw('DATEDIFF(end_date, start_date)')),
            'avg_win_rate' => Campaign::avg('win_rate')
        ];
    }

    private function getSystemChartsData()
    {
        return [
            'daily_revenue' => $this->getDailyRevenueData(),
            'user_activity' => $this->getUserActivityData(),
            'campaign_trends' => $this->getCampaignTrendsData()
        ];
    }

    private function getBestCampaign($campaigns)
    {
        return $campaigns->sortByDesc(function ($campaign) {
            return $campaign->profit_percentage;
        })->first();
    }

    private function getWorstCampaign($campaigns)
    {
        return $campaigns->sortBy(function ($campaign) {
            return $campaign->profit_percentage;
        })->first();
    }

    private function getDailyProfitData($campaigns)
    {
        $data = [];
        $last30Days = Carbon::now()->subDays(30);

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyProfit = 0;

            foreach ($campaigns as $campaign) {
                $dailyBets = $campaign->bets()->whereDate('created_at', $date)->get();
                $dailyProfit += $dailyBets->sum(function ($bet) {
                    return $bet->is_win ? $bet->win_amount - $bet->amount : -$bet->amount;
                });
            }

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'profit' => $dailyProfit
            ];
        }

        return $data;
    }

    private function getWinRateData($campaigns)
    {
        return $campaigns->map(function ($campaign) {
            return [
                'campaign_name' => $campaign->name,
                'win_rate' => $campaign->win_rate
            ];
        });
    }

    private function getCampaignPerformanceData($campaigns)
    {
        return $campaigns->map(function ($campaign) {
            return [
                'name' => $campaign->name,
                'profit' => $campaign->profit,
                'profit_percentage' => $campaign->profit_percentage,
                'win_rate' => $campaign->win_rate,
                'total_bets' => $campaign->total_bet_count
            ];
        });
    }

    private function getTotalProfitToday()
    {
        $today = Carbon::today();
        return CampaignBet::whereDate('created_at', $today)
            ->get()
            ->sum(function ($bet) {
                return $bet->is_win ? $bet->win_amount - $bet->amount : -$bet->amount;
            });
    }

    private function getUserGrowthData()
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'new_users' => User::whereDate('created_at', $date)->count()
            ];
        }
        return $data;
    }

    private function getSubscriptionDistribution()
    {
        return User::select('subscription_type', DB::raw('count(*) as count'))
            ->whereNotNull('subscription_type')
            ->groupBy('subscription_type')
            ->get();
    }

    private function getDailyRevenueData()
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = WalletTransaction::where('type', 'deposit')
                ->whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('amount');

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'revenue' => $revenue
            ];
        }
        return $data;
    }

    private function getUserActivityData()
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $activity = CampaignBet::whereDate('created_at', $date)->count();

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'activity' => $activity
            ];
        }
        return $data;
    }

    private function getCampaignTrendsData()
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $newCampaigns = Campaign::whereDate('created_at', $date)->count();

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'new_campaigns' => $newCampaigns
            ];
        }
        return $data;
    }
}
