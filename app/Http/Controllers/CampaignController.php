<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\LotteryBetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    protected $betService;

    public function __construct(LotteryBetService $betService)
    {
        $this->betService = $betService;
        $this->middleware('auth');
    }

    public function index()
    {
        $campaigns = Campaign::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'days' => 'required|integer|min:1',
            'initial_balance' => 'required|numeric|min:1000',
            'bet_type' => 'required|in:manual,formula'
        ]);

        try {
            $campaign = $this->betService->createCampaign(Auth::id(), $validated);
            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Chiến dịch được tạo thành công.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $bets = $campaign->bets()->latest()->paginate(10);
        $stats = [
            'total_bets' => $campaign->bets()->count(),
            'win_bets' => $campaign->bets()->where('is_win', true)->count(),
            'total_bet_amount' => $campaign->bets()->sum('amount'),
            'total_win_amount' => $campaign->bets()->where('is_win', true)->sum('win_amount'),
        ];

        return view('campaigns.show', compact('campaign', 'bets', 'stats'));
    }

    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);
        $campaign->delete();
        return redirect()->route('campaigns.index')
            ->with('success', 'Chiến dịch đã được xóa.');
    }

    public function showBetForm(Campaign $campaign)
    {
        $this->authorize('view', $campaign);
        return view('campaigns.bet', compact('campaign'));
    }

    public function placeBet(Request $request, Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $validated = $request->validate([
            'bet_date' => 'required|date',
            'lo_number' => 'required|integer|min:0|max:99',
            'points' => 'required|integer|min:1'
        ]);

        try {
            $bet = $this->betService->placeCampaignBet($campaign->id, $validated);
            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Đặt cược thành công');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
