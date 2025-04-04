
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
        return view('campaigns.show', compact('campaign', 'bets'));
    }

    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);
        $campaign->delete();
        return redirect()->route('campaigns.index')
                        ->with('success', 'Chiến dịch đã được xóa.');
    }
}
