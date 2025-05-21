<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignBet;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\CampaignRunJob;

class CampaignController extends Controller
{
    protected $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
        $this->middleware('auth');
    }

    public function index()
    {
        $campaigns = $this->campaignService->search();
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'bet_type' => 'required|string|in:manual,auto',
            // 'bet_amount' => 'required|numeric|min:1000',
            // 'max_bet_amount' => 'required|numeric|min:1000',
            // 'min_bet_amount' => 'required|numeric|min:1000',
            // 'max_bet_per_day' => 'required|integer|min:1',
            // 'max_loss_per_day' => 'required|numeric|min:0',
            // 'max_loss_total' => 'required|numeric|min:0',
            'target_profit' => 'nullable|numeric|min:0',
            'auto_stop_loss' => 'boolean',
            'auto_take_profit' => 'boolean',
            'status' => 'required|string|in:waiting,active,running,paused,finished,completed',
        ]);

        // Tính số ngày
        if (!empty($validated['end_date'])) {
            $start = \Carbon\Carbon::parse($validated['start_date']);
            $end = \Carbon\Carbon::parse($validated['end_date']);
            $validated['days'] = $start->diffInDays($end) + 1;
        } else {
            $validated['days'] = 30;
        }

        // Thêm các trường mặc định
        $validated['status'] = 'waiting';
        $validated['total_bet'] = 0;
        $validated['total_profit'] = 0; 
        $validated['total_bet_count'] = 0;
        $validated['win_rate'] = 0;

        $campaign = $this->campaignService->createWithUser(Auth::user(), $validated);

        return redirect()
            ->route('campaigns.show', $campaign->id)
            ->with('success', 'Tạo chiến dịch thành công!');
    }

    public function show(Campaign $campaign)
    {
        $bets = $this->campaignService->getBets($campaign->id);
        return view('campaigns.show', compact('campaign', 'bets'));
    }

    public function pause(Campaign $campaign)
    {
        $this->campaignService->pause($campaign->id);
        return response()->json(['message' => 'Đã tạm dừng chiến dịch']);
    }

    public function finish(Campaign $campaign)
    {
        $this->campaignService->finish($campaign->id);
        return response()->json(['message' => 'Đã kết thúc chiến dịch']);
    }

    public function storeBet(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'bet_date' => 'required|date',
            'bet_numbers' => 'required|string',
            'bet_amount' => 'required|numeric|min:1000',
        ]);

        // Convert bet_numbers from string to array
        $validated['bet_numbers'] = array_map('trim', explode(',', $validated['bet_numbers']));

        $this->campaignService->addBet($campaign->id, $validated);

        return redirect()
            ->route('campaigns.show', $campaign->id)
            ->with('success', 'Thêm cược thành công!');
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
            $betData = [
                'bet_date' => $validated['bet_date'],
                'bet_numbers' => [$validated['lo_number']],
                'bet_amount' => $validated['points'] * 10000 // 10k/điểm
            ];
            
            $bet = $this->campaignService->addBet($campaign->id, $betData);
            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Đặt cược thành công');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function run(Campaign $campaign)
    {
        if ($campaign->status !== 'running' && $campaign->status !== 'waiting') {
            return response()->json([
                'message' => 'Chiến dịch không ở trạng thái running'
            ], 400);
        }

        // Dispatch job chạy chiến dịch
        CampaignRunJob::dispatch($campaign->id);

        return response()->json([
            'message' => 'Đã bắt đầu chạy chiến dịch'
        ]);
    }
}
