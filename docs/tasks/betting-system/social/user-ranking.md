# Hệ thống Ranking Người dùng

## Tổng quan
Triển khai hệ thống xếp hạng người dùng dựa trên hiệu quả đặt cược, giúp tạo tính cạnh tranh và động lực cho cộng đồng.

## Mục tiêu
- Xếp hạng người dùng theo nhiều tiêu chí khác nhau
- Leaderboard theo thời gian (ngày/tuần/tháng/năm)
- Badge và achievement system
- Social competition features

## Phân tích kỹ thuật

### Database Schema

```sql
CREATE TABLE user_rankings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    ranking_type ENUM('profit', 'win_rate', 'consistency', 'volume') DEFAULT 'profit',
    period ENUM('daily', 'weekly', 'monthly', 'yearly', 'all_time') DEFAULT 'all_time',
    score DECIMAL(15, 4) NOT NULL,
    rank_position INT NOT NULL,
    total_participants INT NOT NULL,
    last_calculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_ranking (user_id, ranking_type, period),
    INDEX idx_ranking_type_period (ranking_type, period),
    INDEX idx_rank_position (rank_position)
);

CREATE TABLE user_achievements (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    achievement_code VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(255),
    points INT DEFAULT 0,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_code),
    INDEX idx_achievements_user_id (user_id)
);
```

### Service Implementation

```php
// app/Services/UserRankingService.php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Campaign;
use App\Models\CampaignBet;
use App\Models\UserRanking;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserRankingService
{
    public function calculateRankings($period = 'all_time', $rankingType = 'profit')
    {
        $users = $this->getUsersWithStats($period);
        
        switch ($rankingType) {
            case 'profit':
                $ranked = $this->rankByProfit($users);
                break;
            case 'win_rate':
                $ranked = $this->rankByWinRate($users);
                break;
            case 'consistency':
                $ranked = $this->rankByConsistency($users);
                break;
            case 'volume':
                $ranked = $this->rankByVolume($users);
                break;
            default:
                $ranked = $this->rankByProfit($users);
        }

        $this->updateRankings($ranked, $rankingType, $period);
        return $ranked;
    }

    public function getLeaderboard($rankingType = 'profit', $period = 'all_time', $limit = 100)
    {
        return Cache::remember("leaderboard_{$rankingType}_{$period}_{$limit}", 3600, function () use ($rankingType, $period, $limit) {
            return UserRanking::with('user')
                ->where('ranking_type', $rankingType)
                ->where('period', $period)
                ->orderBy('rank_position')
                ->limit($limit)
                ->get();
        });
    }

    public function getUserRank($userId, $rankingType = 'profit', $period = 'all_time')
    {
        return UserRanking::where('user_id', $userId)
            ->where('ranking_type', $rankingType)
            ->where('period', $period)
            ->first();
    }

    private function getUsersWithStats($period)
    {
        $dateFilter = $this->getDateFilter($period);
        
        return User::select('users.*')
            ->selectRaw('
                COALESCE(SUM(campaigns.current_balance - campaigns.initial_balance), 0) as total_profit,
                COALESCE(COUNT(DISTINCT campaigns.id), 0) as total_campaigns,
                COALESCE(SUM(campaigns.total_bet_amount), 0) as total_volume,
                COALESCE(AVG(campaigns.win_rate), 0) as avg_win_rate
            ')
            ->leftJoin('campaigns', 'users.id', '=', 'campaigns.user_id')
            ->when($dateFilter, function ($query) use ($dateFilter) {
                $query->where('campaigns.created_at', '>=', $dateFilter);
            })
            ->groupBy('users.id')
            ->having('total_campaigns', '>', 0)
            ->get();
    }

    private function rankByProfit($users)
    {
        return $users->sortByDesc('total_profit')->values();
    }

    private function rankByWinRate($users)
    {
        return $users->sortByDesc('avg_win_rate')->values();
    }

    private function rankByConsistency($users)
    {
        // Calculate consistency score based on profit volatility
        return $users->map(function ($user) {
            $campaigns = Campaign::where('user_id', $user->id)->get();
            $profits = $campaigns->pluck('profit');
            
            if ($profits->count() < 2) {
                $user->consistency_score = 0;
                return $user;
            }

            $avgProfit = $profits->avg();
            $variance = $profits->map(fn($p) => pow($p - $avgProfit, 2))->avg();
            $stdDev = sqrt($variance);
            
            // Higher consistency = lower standard deviation
            $user->consistency_score = $avgProfit / max($stdDev, 1);
            return $user;
        })->sortByDesc('consistency_score')->values();
    }

    private function rankByVolume($users)
    {
        return $users->sortByDesc('total_volume')->values();
    }

    private function updateRankings($rankedUsers, $rankingType, $period)
    {
        $totalParticipants = $rankedUsers->count();
        
        $rankedUsers->each(function ($user, $index) use ($rankingType, $period, $totalParticipants) {
            $score = $this->getScoreForRankingType($user, $rankingType);
            
            UserRanking::updateOrCreate([
                'user_id' => $user->id,
                'ranking_type' => $rankingType,
                'period' => $period
            ], [
                'score' => $score,
                'rank_position' => $index + 1,
                'total_participants' => $totalParticipants,
                'last_calculated' => now()
            ]);
        });
    }

    private function getScoreForRankingType($user, $rankingType)
    {
        switch ($rankingType) {
            case 'profit':
                return $user->total_profit;
            case 'win_rate':
                return $user->avg_win_rate;
            case 'consistency':
                return $user->consistency_score ?? 0;
            case 'volume':
                return $user->total_volume;
            default:
                return 0;
        }
    }

    private function getDateFilter($period)
    {
        switch ($period) {
            case 'daily':
                return now()->startOfDay();
            case 'weekly':
                return now()->startOfWeek();
            case 'monthly':
                return now()->startOfMonth();
            case 'yearly':
                return now()->startOfYear();
            default:
                return null;
        }
    }

    public function checkAndAwardAchievements($userId)
    {
        $achievements = [
            'first_profit' => [
                'condition' => fn($user) => $this->getUserTotalProfit($user->id) > 0,
                'title' => 'Lần đầu có lãi',
                'description' => 'Đạt được lợi nhuận đầu tiên',
                'points' => 100
            ],
            'top_10' => [
                'condition' => fn($user) => $this->getUserRank($user->id)?->rank_position <= 10,
                'title' => 'Top 10',
                'description' => 'Lọt vào top 10 người dùng tốt nhất',
                'points' => 500
            ],
            'win_streak_10' => [
                'condition' => fn($user) => $this->getUserMaxWinStreak($user->id) >= 10,
                'title' => 'Thắng liên tiếp 10 lần',
                'description' => 'Đạt được chuỗi thắng 10 lần liên tiếp',
                'points' => 300
            ]
        ];

        $user = User::find($userId);
        
        foreach ($achievements as $code => $achievement) {
            if ($achievement['condition']($user)) {
                $this->awardAchievement($userId, $code, $achievement);
            }
        }
    }

    private function awardAchievement($userId, $code, $achievement)
    {
        UserAchievement::firstOrCreate([
            'user_id' => $userId,
            'achievement_code' => $code
        ], [
            'title' => $achievement['title'],
            'description' => $achievement['description'],
            'points' => $achievement['points']
        ]);
    }

    private function getUserTotalProfit($userId)
    {
        return Campaign::where('user_id', $userId)
            ->sum(DB::raw('current_balance - initial_balance'));
    }

    private function getUserMaxWinStreak($userId)
    {
        $bets = CampaignBet::whereHas('campaign', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->orderBy('created_at')
            ->get(['is_win']);

        $maxStreak = 0;
        $currentStreak = 0;

        foreach ($bets as $bet) {
            if ($bet->is_win) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        return $maxStreak;
    }
}
```

### Controller

```php
// app/Http/Controllers/RankingController.php
<?php

namespace App\Http\Controllers;

use App\Services\UserRankingService;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    private UserRankingService $rankingService;

    public function __construct(UserRankingService $rankingService)
    {
        $this->rankingService = $rankingService;
    }

    public function index(Request $request)
    {
        $rankingType = $request->get('type', 'profit');
        $period = $request->get('period', 'all_time');
        
        $leaderboard = $this->rankingService->getLeaderboard($rankingType, $period);
        $userRank = auth()->check() ? 
            $this->rankingService->getUserRank(auth()->id(), $rankingType, $period) : null;

        return view('ranking.index', compact('leaderboard', 'userRank', 'rankingType', 'period'));
    }

    public function achievements(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $achievements = UserAchievement::where('user_id', auth()->id())
            ->orderBy('unlocked_at', 'desc')
            ->get();

        return view('ranking.achievements', compact('achievements'));
    }
}
```

### Blade Templates

```php
{{-- resources/views/ranking/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Bảng xếp hạng')

@section('content')
<div class="max-w-6xl mx-auto py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Bảng xếp hạng</h1>
        <p class="text-gray-600">Xem thứ hạng của bạn và các trader hàng đầu</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-wrap gap-4">
            <select name="type" class="rounded-md border-gray-300">
                <option value="profit" {{ $rankingType == 'profit' ? 'selected' : '' }}>Lợi nhuận</option>
                <option value="win_rate" {{ $rankingType == 'win_rate' ? 'selected' : '' }}>Tỷ lệ thắng</option>
                <option value="consistency" {{ $rankingType == 'consistency' ? 'selected' : '' }}>Ổn định</option>
                <option value="volume" {{ $rankingType == 'volume' ? 'selected' : '' }}>Khối lượng</option>
            </select>
            
            <select name="period" class="rounded-md border-gray-300">
                <option value="all_time" {{ $period == 'all_time' ? 'selected' : '' }}>Tất cả thời gian</option>
                <option value="yearly" {{ $period == 'yearly' ? 'selected' : '' }}>Năm nay</option>
                <option value="monthly" {{ $period == 'monthly' ? 'selected' : '' }}>Tháng này</option>
                <option value="weekly" {{ $period == 'weekly' ? 'selected' : '' }}>Tuần này</option>
            </select>
        </div>
    </div>

    <!-- User's Rank -->
    @if($userRank)
        <div class="bg-blue-50 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-blue-900">Thứ hạng của bạn</h3>
            <p class="text-blue-800">
                #{{ $userRank->rank_position }} / {{ $userRank->total_participants }} 
                (Score: {{ number_format($userRank->score, 2) }})
            </p>
        </div>
    @endif

    <!-- Leaderboard -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hạng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người dùng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Điểm</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thành tích</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($leaderboard as $ranking)
                    <tr class="{{ $ranking->user_id == auth()->id() ? 'bg-blue-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($ranking->rank_position <= 3)
                                    <span class="text-2xl">
                                        @if($ranking->rank_position == 1) 🥇
                                        @elseif($ranking->rank_position == 2) 🥈
                                        @else 🥉
                                        @endif
                                    </span>
                                @endif
                                <span class="ml-2 font-bold">#{{ $ranking->rank_position }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img src="{{ $ranking->user->profile->avatar_url ?? '' }}" 
                                     alt="Avatar" class="w-8 h-8 rounded-full mr-3">
                                <span class="font-medium">{{ $ranking->user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-lg font-semibold">{{ number_format($ranking->score, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $ranking->last_calculated->diffForHumans() }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('select[name="type"], select[name="period"]').forEach(select => {
    select.addEventListener('change', function() {
        const url = new URL(window.location);
        url.searchParams.set(this.name, this.value);
        window.location = url;
    });
});
</script>
@endsection
```

## Kết luận

Hệ thống ranking cung cấp:
- ✅ Multiple ranking criteria
- ✅ Time-based leaderboards  
- ✅ Achievement system
- ✅ Social competition features
- ✅ Real-time updates

**Thời gian ước tính**: 3 ngày
**Priority**: Medium
**Dependencies**: Campaign system, User management 
