<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Campaign;
use App\Services\SocialService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SocialController extends Controller
{
    protected $socialService;

    public function __construct(SocialService $socialService)
    {
        $this->socialService = $socialService;
        $this->middleware('auth');
    }

    // Social Following
    public function index()
    {
        $user = auth()->user();

        $data = [
            'following_campaigns' => $this->socialService->getFollowingCampaigns($user),
            'top_performers' => $this->socialService->getTopPerformers(),
            'social_feed' => $this->socialService->getSocialFeed($user),
            'leaderboard' => $this->socialService->getLeaderboard('month', 20),
            'user_stats' => $this->socialService->getUserPerformanceStats($user)
        ];

        return view('social.index', $data);
    }

    public function follow(Request $request, User $user): JsonResponse
    {
        $follower = auth()->user();
        $result = $this->socialService->followUser($follower, $user);

        return response()->json($result);
    }

    public function unfollow(Request $request, User $user): JsonResponse
    {
        $follower = auth()->user();
        $result = $this->socialService->unfollowUser($follower, $user);

        return response()->json($result);
    }

    public function followers(User $user)
    {
        $followers = $user->followers()
            ->with('follower:id,name,avatar')
            ->paginate(20);

        return view('social.followers', [
            'user' => $user,
            'followers' => $followers
        ]);
    }

    public function following(User $user)
    {
        $following = $user->following()
            ->with('following:id,name,avatar')
            ->paginate(20);

        return view('social.following', [
            'user' => $user,
            'following' => $following
        ]);
    }

    // Campaign Sharing
    public function shareCampaign(Request $request, Campaign $campaign): JsonResponse
    {
        $request->validate([
            'platform' => 'required|string|in:facebook,twitter,telegram,copy_link'
        ]);

        $user = auth()->user();
        $result = $this->socialService->shareCampaign(
            $campaign,
            $user,
            $request->platform
        );

        return response()->json($result);
    }

    public function sharedCampaign(Request $request, Campaign $campaign)
    {
        // Track the click if there's a share reference
        if ($request->has('ref') && $request->has('share_id')) {
            $this->socialService->trackShareClick(
                $request->share_id,
                [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'referrer' => $request->header('referer')
                ]
            );
        }

        // Show public campaign view
        return view('campaigns.public', [
            'campaign' => $campaign->load(['user', 'bets' => function ($query) {
                $query->latest()->limit(10);
            }]),
            'share_stats' => $this->socialService->getCampaignShareStats($campaign)
        ]);
    }

    // Leaderboard & Rankings
    public function leaderboard(Request $request)
    {
        $period = $request->get('period', 'month');
        $leaderboard = $this->socialService->getLeaderboard($period, 50);

        if ($request->ajax()) {
            return response()->json(['leaderboard' => $leaderboard]);
        }

        return view('social.leaderboard', [
            'leaderboard' => $leaderboard,
            'period' => $period
        ]);
    }

    public function topPerformers(): JsonResponse
    {
        $performers = $this->socialService->getTopPerformers(20);
        return response()->json(['performers' => $performers]);
    }

    // Social Feed API
    public function feed(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->get('limit', 20);

        $feed = $this->socialService->getSocialFeed($user, $limit);

        return response()->json($feed);
    }

    public function followingCampaigns(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->get('limit', 20);

        $campaigns = $this->socialService->getFollowingCampaigns($user, $limit);

        return response()->json($campaigns);
    }

    // User Profile
    public function profile(User $user)
    {
        $currentUser = auth()->user();

        $data = [
            'user' => $user,
            'is_following' => $currentUser->isFollowing($user->id),
            'performance_stats' => $this->socialService->getUserPerformanceStats($user),
            'public_campaigns' => $user->campaigns()
                ->where('is_public', true)
                ->with('bets')
                ->orderBy('created_at', 'desc')
                ->paginate(10),
            'followers_count' => $user->followers_count,
            'following_count' => $user->following_count
        ];

        return view('social.profile', $data);
    }

    // Search Users
    public function searchUsers(Request $request): JsonResponse
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $users = User::where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->select('id', 'name', 'email', 'avatar')
            ->limit(10)
            ->get();

        return response()->json(['users' => $users]);
    }

    // Campaign Recommendations
    public function recommendCampaigns(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Get campaigns from users with similar performance
        $recommendations = Campaign::where('is_public', true)
            ->where('status', 'active')
            ->where('user_id', '!=', $user->id)
            ->whereNotIn('user_id', $user->following()->pluck('following_id'))
            ->with(['user', 'bets' => function ($query) {
                $query->latest()->limit(3);
            }])
            ->orderByRaw('(current_balance - initial_balance) / initial_balance DESC')
            ->limit(10)
            ->get();

        return response()->json(['recommendations' => $recommendations]);
    }

    // Analytics
    public function shareAnalytics(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $stats = $this->socialService->getCampaignShareStats($campaign);

        return response()->json($stats);
    }
}
