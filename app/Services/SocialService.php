<?php

namespace App\Services;

use App\Models\User;
use App\Models\Campaign;
use App\Models\CampaignShare;
use App\Models\SocialFollow;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class SocialService
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    // Social Following Features
    public function followUser(User $follower, User $following): array
    {
        if ($follower->id === $following->id) {
            return ['success' => false, 'message' => 'Không thể follow chính mình'];
        }

        if ($follower->isFollowing($following->id)) {
            return ['success' => false, 'message' => 'Đã follow user này rồi'];
        }

        $follow = SocialFollow::create([
            'follower_id' => $follower->id,
            'following_id' => $following->id
        ]);

        return [
            'success' => true,
            'message' => "Đã follow {$following->name}",
            'follow' => $follow
        ];
    }

    public function unfollowUser(User $follower, User $following): array
    {
        $deleted = SocialFollow::where('follower_id', $follower->id)
            ->where('following_id', $following->id)
            ->delete();

        if ($deleted) {
            return [
                'success' => true,
                'message' => "Đã unfollow {$following->name}"
            ];
        }

        return [
            'success' => false,
            'message' => 'Không tìm thấy follow relationship'
        ];
    }

    public function getFollowingCampaigns(User $user, int $limit = 20): array
    {
        $followingIds = $user->following()->pluck('following_id');

        $campaigns = Campaign::whereIn('user_id', $followingIds)
            ->where('is_public', true)
            ->where('status', 'active')
            ->with(['user:id,name,avatar', 'bets' => function ($query) {
                $query->latest()->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'campaigns' => $campaigns,
            'following_count' => $followingIds->count()
        ];
    }

    public function getTopPerformers(int $limit = 10): array
    {
        $topUsers = User::select('users.*')
            ->join('campaigns', 'users.id', '=', 'campaigns.user_id')
            ->where('campaigns.status', 'active')
            ->where('campaigns.is_public', true)
            ->groupBy('users.id')
            ->orderByRaw('AVG((campaigns.current_balance - campaigns.initial_balance) / campaigns.initial_balance) DESC')
            ->havingRaw('COUNT(campaigns.id) >= 3') // At least 3 campaigns
            ->limit($limit)
            ->get();

        return $topUsers->map(function ($user) {
            $performance = $this->getUserPerformanceStats($user);
            return array_merge($user->toArray(), $performance);
        })->toArray();
    }

    public function getUserPerformanceStats(User $user): array
    {
        $campaigns = $user->campaigns()
            ->where('is_public', true)
            ->get();

        $totalProfit = $campaigns->sum(function ($campaign) {
            return $campaign->current_balance - $campaign->initial_balance;
        });

        $totalInvestment = $campaigns->sum('initial_balance');
        $profitRate = $totalInvestment > 0 ? ($totalProfit / $totalInvestment) * 100 : 0;

        $activeCampaigns = $campaigns->where('status', 'active')->count();
        $winRate = $this->calculateUserWinRate($user);

        return [
            'total_profit' => $totalProfit,
            'profit_rate' => round($profitRate, 2),
            'active_campaigns' => $activeCampaigns,
            'win_rate' => round($winRate, 2),
            'followers_count' => $user->followers_count,
            'following_count' => $user->following_count
        ];
    }

    private function calculateUserWinRate(User $user): float
    {
        $totalBets = 0;
        $winBets = 0;

        $user->campaigns->each(function ($campaign) use (&$totalBets, &$winBets) {
            $totalBets += $campaign->bets->count();
            $winBets += $campaign->bets->where('is_win', true)->count();
        });

        return $totalBets > 0 ? ($winBets / $totalBets) * 100 : 0;
    }

    // Campaign Sharing Features
    public function shareCampaign(Campaign $campaign, User $user, string $platform): array
    {
        if (!$campaign->is_public) {
            return ['success' => false, 'message' => 'Campaign không public'];
        }

        $shareUrl = $this->generateShareUrl($campaign, $platform, $user);

        $share = CampaignShare::create([
            'campaign_id' => $campaign->id,
            'shared_by_user_id' => $user->id,
            'share_platform' => $platform,
            'share_url' => $shareUrl,
            'click_count' => 0
        ]);

        return [
            'success' => true,
            'message' => 'Campaign đã được share',
            'share_url' => $shareUrl,
            'share' => $share
        ];
    }

    protected function generateShareUrl(Campaign $campaign, string $platform, User $user): string
    {
        $baseUrl = route('campaigns.shared', [
            'campaign' => $campaign->id,
            'ref' => $user->id
        ]);

        $shareData = $this->buildShareData($campaign);

        switch ($platform) {
            case 'facebook':
                return 'https://www.facebook.com/sharer/sharer.php?' . http_build_query([
                    'u' => $baseUrl,
                    'quote' => $shareData['text']
                ]);

            case 'twitter':
                return 'https://twitter.com/intent/tweet?' . http_build_query([
                    'text' => $shareData['text'],
                    'url' => $baseUrl,
                    'hashtags' => 'xsmb,lottery,campaign'
                ]);

            case 'telegram':
                return 'https://t.me/share/url?' . http_build_query([
                    'url' => $baseUrl,
                    'text' => $shareData['text']
                ]);

            case 'copy_link':
            default:
                return $baseUrl;
        }
    }

    protected function buildShareData(Campaign $campaign): array
    {
        $profitRate = $campaign->initial_balance > 0
            ? (($campaign->current_balance - $campaign->initial_balance) / $campaign->initial_balance) * 100
            : 0;

        $text = "🎯 Campaign XSMB: {$campaign->name}\n";
        $text .= "💰 Lợi nhuận: " . number_format($profitRate, 1) . "%\n";
        $text .= "📊 Tỷ lệ thắng: " . number_format($campaign->win_rate, 1) . "%\n";
        $text .= "👤 Trader: {$campaign->user->name}";

        return [
            'title' => "Campaign: {$campaign->name}",
            'text' => $text,
            'image' => $this->generateCampaignImage($campaign)
        ];
    }

    protected function generateCampaignImage(Campaign $campaign): string
    {
        // Generate a simple image URL with campaign stats
        // This could be enhanced with actual image generation
        return route('api.campaigns.image', $campaign->id);
    }

    public function trackShareClick(int $shareId, array $analytics = []): void
    {
        $share = CampaignShare::find($shareId);

        if ($share) {
            $share->incrementClickCount();

            if (!empty($analytics)) {
                $share->addAnalytics($analytics);
            }
        }
    }

    public function getCampaignShareStats(Campaign $campaign): array
    {
        $shares = $campaign->shares;

        $stats = [
            'total_shares' => $shares->count(),
            'total_clicks' => $shares->sum('click_count'),
            'platforms' => $shares->groupBy('share_platform')->map(function ($platformShares) {
                return [
                    'shares' => $platformShares->count(),
                    'clicks' => $platformShares->sum('click_count')
                ];
            }),
            'top_sharers' => $shares->groupBy('shared_by_user_id')
                ->map(function ($userShares) {
                    $user = $userShares->first()->sharedBy;
                    return [
                        'user' => $user,
                        'shares' => $userShares->count(),
                        'clicks' => $userShares->sum('click_count')
                    ];
                })
                ->sortByDesc('clicks')
                ->take(5)
                ->values()
        ];

        return $stats;
    }

    // Social Feed
    public function getSocialFeed(User $user, int $limit = 20): array
    {
        $followingIds = $user->following()->pluck('following_id');

        // Get following users' activities
        $activities = collect();

        // Recent campaigns from following
        $recentCampaigns = Campaign::whereIn('user_id', $followingIds)
            ->where('is_public', true)
            ->with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($campaign) {
                return [
                    'type' => 'campaign_created',
                    'timestamp' => $campaign->created_at,
                    'user' => $campaign->user,
                    'data' => $campaign
                ];
            });

        // Big wins from following
        $bigWins = \DB::table('campaign_bets')
            ->join('campaigns', 'campaign_bets.campaign_id', '=', 'campaigns.id')
            ->join('users', 'campaigns.user_id', '=', 'users.id')
            ->whereIn('campaigns.user_id', $followingIds)
            ->where('campaigns.is_public', true)
            ->where('campaign_bets.is_win', true)
            ->where('campaign_bets.win_amount', '>', 100000) // Big wins > 100k
            ->select(
                'campaign_bets.*',
                'campaigns.name as campaign_name',
                'users.name as user_name',
                'users.avatar as user_avatar'
            )
            ->orderBy('campaign_bets.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($bet) {
                return [
                    'type' => 'big_win',
                    'timestamp' => $bet->created_at,
                    'user' => [
                        'name' => $bet->user_name,
                        'avatar' => $bet->user_avatar
                    ],
                    'data' => $bet
                ];
            });

        $activities = $activities->concat($recentCampaigns)
            ->concat($bigWins)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return [
            'activities' => $activities,
            'following_count' => $followingIds->count()
        ];
    }

    // Leaderboard
    public function getLeaderboard(string $period = 'month', int $limit = 50): array
    {
        $startDate = $this->getPeriodStartDate($period);

        $leaderboard = User::select('users.*')
            ->join('campaigns', 'users.id', '=', 'campaigns.user_id')
            ->where('campaigns.is_public', true)
            ->where('campaigns.created_at', '>=', $startDate)
            ->groupBy('users.id')
            ->selectRaw('
                users.*,
                SUM(campaigns.current_balance - campaigns.initial_balance) as total_profit,
                AVG((campaigns.current_balance - campaigns.initial_balance) / campaigns.initial_balance * 100) as avg_profit_rate,
                COUNT(campaigns.id) as campaign_count
            ')
            ->orderBy('total_profit', 'desc')
            ->limit($limit)
            ->get();

        return $leaderboard->map(function ($user, $index) {
            return [
                'rank' => $index + 1,
                'user' => $user,
                'total_profit' => $user->total_profit,
                'avg_profit_rate' => round($user->avg_profit_rate, 2),
                'campaign_count' => $user->campaign_count,
                'followers_count' => $user->followers_count
            ];
        })->toArray();
    }

    private function getPeriodStartDate(string $period): \Carbon\Carbon
    {
        switch ($period) {
            case 'week':
                return now()->startOfWeek();
            case 'month':
                return now()->startOfMonth();
            case 'year':
                return now()->startOfYear();
            default:
                return now()->startOfMonth();
        }
    }
}
