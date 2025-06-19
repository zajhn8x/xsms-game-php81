<?php

namespace App\Http\Controllers;

use App\Services\HistoricalTestingService;
use App\Models\HistoricalCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoricalTestingController extends Controller
{
    protected $historicalTestingService;

    public function __construct(HistoricalTestingService $historicalTestingService)
    {
        $this->middleware('auth');
        $this->historicalTestingService = $historicalTestingService;
    }

    public function index()
    {
        $campaigns = HistoricalCampaign::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('historical-testing.index', compact('campaigns'));
    }

    public function create()
    {
        return view('historical-testing.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'test_start_date' => 'required|date|before:test_end_date',
            'test_end_date' => 'required|date|after:test_start_date',
            'data_start_date' => 'nullable|date|before_or_equal:test_start_date',
            'data_end_date' => 'nullable|date|after_or_equal:test_end_date',
            'initial_balance' => 'required|numeric|min:10000',
            'betting_strategy' => 'required|in:manual,auto_heatmap,auto_streak,hybrid',
            'strategy_config' => 'nullable|array'
        ]);

        try {
            $campaign = $this->historicalTestingService->createTestCampaign(
                Auth::id(),
                $request->all()
            );

            return redirect()->route('historical-testing.show', $campaign->id)
                ->with('success', 'Chiến dịch thử nghiệm đã được tạo thành công');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi tạo chiến dịch: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $campaign = HistoricalCampaign::where('user_id', Auth::id())
            ->findOrFail($id);

        $results = $this->historicalTestingService->calculateResults($campaign);
        $betHistory = $this->historicalTestingService->getBetHistory($id);

        return view('historical-testing.show', compact('campaign', 'results', 'betHistory'));
    }

    public function run(Request $request, $id)
    {
        $campaign = HistoricalCampaign::where('user_id', Auth::id())
            ->findOrFail($id);

        if ($campaign->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chiến dịch đã được chạy hoặc đang chạy'
            ], 400);
        }

        try {
            // Queue the job for background processing
            \App\Jobs\RunHistoricalTestJob::dispatch($campaign->id);

            return response()->json([
                'success' => true,
                'message' => 'Đã bắt đầu chạy test. Vui lòng đợi...'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function status($id)
    {
        $campaign = HistoricalCampaign::where('user_id', Auth::id())
            ->findOrFail($id);

        $progress = 0;
        if ($campaign->status === 'running') {
            // Calculate progress based on processed days
            $totalDays = $campaign->test_start_date->diffInDays($campaign->test_end_date) + 1;
            $processedDays = $campaign->bets()->distinct('bet_date')->count();
            $progress = $totalDays > 0 ? round(($processedDays / $totalDays) * 100, 2) : 0;
        } elseif ($campaign->status === 'completed') {
            $progress = 100;
        }

        return response()->json([
            'status' => $campaign->status,
            'progress' => $progress,
            'final_balance' => $campaign->final_balance,
            'profit' => $campaign->profit,
            'profit_percentage' => $campaign->profit_percentage
        ]);
    }

    public function destroy($id)
    {
        $campaign = HistoricalCampaign::where('user_id', Auth::id())
            ->findOrFail($id);

        if ($campaign->status === 'running') {
            return back()->with('error', 'Không thể xóa chiến dịch đang chạy');
        }

        $campaign->delete();

        return redirect()->route('historical-testing.index')
            ->with('success', 'Đã xóa chiến dịch thành công');
    }

    // API endpoints for AJAX
    public function apiIndex(Request $request)
    {
        $query = HistoricalCampaign::where('user_id', Auth::id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('strategy')) {
            $query->where('betting_strategy', $request->strategy);
        }

        $campaigns = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($campaigns);
    }

    public function apiBetHistory($id)
    {
        $campaign = HistoricalCampaign::where('user_id', Auth::id())
            ->findOrFail($id);

        $bets = $campaign->bets()
            ->orderBy('bet_date', 'desc')
            ->paginate(20);

        return response()->json($bets);
    }

    public function apiResults($id)
    {
        $campaign = HistoricalCampaign::where('user_id', Auth::id())
            ->findOrFail($id);

        $results = $this->historicalTestingService->calculateResults($campaign);

        return response()->json($results);
    }

    // Strategy configuration helper
    public function getStrategyConfig(Request $request)
    {
        $strategy = $request->get('strategy');

        $configs = [
            'manual' => [
                'description' => 'Chiến lược thủ công - tự định nghĩa pattern đặt cược',
                'fields' => []
            ],
            'auto_heatmap' => [
                'description' => 'Tự động dựa trên heatmap - đặt cược số có điểm nóng cao',
                'fields' => [
                    'min_heat_score' => ['type' => 'number', 'label' => 'Điểm heat tối thiểu', 'default' => 70],
                    'max_numbers_per_day' => ['type' => 'number', 'label' => 'Số lượng tối đa/ngày', 'default' => 3],
                    'base_bet_amount' => ['type' => 'number', 'label' => 'Số tiền cơ bản', 'default' => 10000]
                ]
            ],
            'auto_streak' => [
                'description' => 'Tự động dựa trên streak - đặt cược số có chuỗi dài',
                'fields' => [
                    'min_streak_days' => ['type' => 'number', 'label' => 'Chuỗi tối thiểu (ngày)', 'default' => 7],
                    'max_streak_days' => ['type' => 'number', 'label' => 'Chuỗi tối đa (ngày)', 'default' => 30],
                    'base_bet_amount' => ['type' => 'number', 'label' => 'Số tiền cơ bản', 'default' => 10000],
                    'multiplier_per_day' => ['type' => 'number', 'label' => 'Hệ số nhân/ngày', 'default' => 0.1]
                ]
            ],
            'hybrid' => [
                'description' => 'Kết hợp heatmap và streak',
                'fields' => [
                    'heat_weight' => ['type' => 'number', 'label' => 'Trọng số heatmap', 'default' => 0.6],
                    'streak_weight' => ['type' => 'number', 'label' => 'Trọng số streak', 'default' => 0.4],
                    'min_combined_score' => ['type' => 'number', 'label' => 'Điểm kết hợp tối thiểu', 'default' => 60],
                    'base_bet_amount' => ['type' => 'number', 'label' => 'Số tiền cơ bản', 'default' => 10000]
                ]
            ]
        ];

        return response()->json($configs[$strategy] ?? []);
    }
}
