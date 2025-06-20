<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\SubCampaign;
use App\Services\SubCampaignService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Micro-task 2.1.4.5: Sub-campaign UI components (5h)
 * Micro-task 2.1.4.6: Sub-campaign monitoring (4h)
 * Controller for managing sub-campaigns
 */
class SubCampaignController extends Controller
{
    protected SubCampaignService $subCampaignService;

    public function __construct(SubCampaignService $subCampaignService)
    {
        $this->subCampaignService = $subCampaignService;
        $this->middleware('auth');
    }

    /**
     * Display sub-campaigns for a campaign
     */
    public function index(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $subCampaigns = $campaign->subCampaigns()
            ->orderBy('priority')
            ->orderBy('created_at', 'desc')
            ->get();

        $aggregation = $this->subCampaignService->aggregatePerformance($campaign);

        return view('campaigns.sub-campaigns', compact('campaign', 'subCampaigns', 'aggregation'));
    }

    /**
     * Store a new sub-campaign
     */
    public function store(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:segment,test,backup,split',
            'allocated_balance' => 'required|numeric|min:10000|max:' . $campaign->current_balance,
            'priority' => 'required|integer|min:1|max:10',
            'betting_strategy' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'auto_start' => 'boolean',
            'auto_stop' => 'boolean'
        ]);

        try {
            $subCampaign = $this->subCampaignService->createSingleSubCampaign($campaign, $validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sub-campaign đã được tạo thành công',
                    'sub_campaign' => $subCampaign
                ]);
            }

            return redirect()->route('campaigns.sub-campaigns.index', $campaign)
                ->with('success', 'Sub-campaign đã được tạo thành công');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Split campaign into multiple sub-campaigns
     */
    public function split(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'splits' => 'required|array|min:2',
            'splits.*.name' => 'required|string|max:255',
            'splits.*.percentage' => 'required|numeric|min:1|max:100'
        ]);

        // Validate total percentage equals 100
        $totalPercentage = array_sum(array_column($validated['splits'], 'percentage'));
        if ($totalPercentage !== 100) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tổng phần trăm phải bằng 100%'
                ], 400);
            }
            return back()->withErrors(['error' => 'Tổng phần trăm phải bằng 100%']);
        }

        try {
            $subCampaigns = $this->subCampaignService->splitCampaignByPercentage($campaign, $validated['splits']);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Campaign đã được chia thành ' . count($subCampaigns) . ' sub-campaigns',
                    'sub_campaigns' => $subCampaigns
                ]);
            }

            return redirect()->route('campaigns.sub-campaigns.index', $campaign)
                ->with('success', 'Campaign đã được chia thành ' . count($subCampaigns) . ' sub-campaigns');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show sub-campaign details
     */
    public function show(Campaign $campaign, SubCampaign $subCampaign)
    {
        $this->authorize('view', $campaign);

        if ($subCampaign->parent_campaign_id !== $campaign->id) {
            abort(404);
        }

        $performance = $subCampaign->getPerformanceSummary();
        $recentBets = $subCampaign->bets()
            ->with('campaign')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('campaigns.sub-campaign-details', compact('campaign', 'subCampaign', 'performance', 'recentBets'));
    }

    /**
     * Start a sub-campaign
     */
    public function start(Campaign $campaign, SubCampaign $subCampaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        if ($subCampaign->parent_campaign_id !== $campaign->id) {
            return response()->json(['success' => false, 'message' => 'Sub-campaign không thuộc campaign này'], 404);
        }

        try {
            $success = $subCampaign->start();

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Sub-campaign đã được khởi động' : 'Không thể khởi động sub-campaign'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Pause a sub-campaign
     */
    public function pause(Campaign $campaign, SubCampaign $subCampaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        if ($subCampaign->parent_campaign_id !== $campaign->id) {
            return response()->json(['success' => false, 'message' => 'Sub-campaign không thuộc campaign này'], 404);
        }

        try {
            $success = $subCampaign->pause();

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Sub-campaign đã được tạm dừng' : 'Không thể tạm dừng sub-campaign'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Resume a sub-campaign
     */
    public function resume(Campaign $campaign, SubCampaign $subCampaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        if ($subCampaign->parent_campaign_id !== $campaign->id) {
            return response()->json(['success' => false, 'message' => 'Sub-campaign không thuộc campaign này'], 404);
        }

        try {
            $success = $subCampaign->resume();

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Sub-campaign đã được tiếp tục' : 'Không thể tiếp tục sub-campaign'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Stop a sub-campaign
     */
    public function stop(Campaign $campaign, SubCampaign $subCampaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        if ($subCampaign->parent_campaign_id !== $campaign->id) {
            return response()->json(['success' => false, 'message' => 'Sub-campaign không thuộc campaign này'], 404);
        }

        try {
            $success = $subCampaign->stop('manual');

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Sub-campaign đã được dừng và số dư đã trả về campaign chính' : 'Không thể dừng sub-campaign'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a sub-campaign
     */
    public function destroy(Campaign $campaign, SubCampaign $subCampaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        if ($subCampaign->parent_campaign_id !== $campaign->id) {
            return response()->json(['success' => false, 'message' => 'Sub-campaign không thuộc campaign này'], 404);
        }

        if ($subCampaign->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể xóa sub-campaign đang ở trạng thái pending'
            ], 400);
        }

        try {
            // Return balance to parent campaign
            if ($subCampaign->current_balance > 0) {
                $campaign->increment('current_balance', $subCampaign->current_balance);
            }

            $subCampaign->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sub-campaign đã được xóa và số dư đã trả về campaign chính'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get monitoring data for sub-campaigns
     */
    public function monitoring(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        try {
            $aggregation = $this->subCampaignService->aggregatePerformance($campaign);

            $autoStarted = $this->subCampaignService->autoStartSubCampaigns($campaign);
            $autoStopped = $this->subCampaignService->autoStopSubCampaigns($campaign);

            $recentActivity = $campaign->subCampaigns()
                ->where('updated_at', '>=', now()->subHours(24))
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'name', 'status', 'updated_at'])
                ->map(function ($subCampaign) {
                    return [
                        'id' => $subCampaign->id,
                        'name' => $subCampaign->name,
                        'status' => $subCampaign->status,
                        'updated_at' => $subCampaign->updated_at->format('H:i d/m/Y')
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'aggregation' => $aggregation,
                    'automation' => [
                        'auto_started' => $autoStarted,
                        'auto_stopped' => $autoStopped
                    ],
                    'recent_activity' => $recentActivity,
                    'alerts' => $this->generateAlerts($campaign, $aggregation)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rebalance sub-campaigns
     */
    public function rebalance(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'allocations' => 'required|array',
            'allocations.*' => 'required|numeric|min:0'
        ]);

        try {
            $success = $this->subCampaignService->rebalanceSubCampaigns($campaign, $validated['allocations']);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Sub-campaigns đã được rebalance thành công' : 'Không thể rebalance sub-campaigns'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get real-time performance data
     */
    public function performanceData(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        try {
            $subCampaigns = $campaign->subCampaigns()
                ->orderBy('priority')
                ->get();

            $performanceData = $subCampaigns->map(function ($subCampaign) {
                return [
                    'id' => $subCampaign->id,
                    'name' => $subCampaign->name,
                    'status' => $subCampaign->status,
                    'current_balance' => $subCampaign->current_balance,
                    'allocated_balance' => $subCampaign->allocated_balance,
                    'profit_loss' => $subCampaign->profit_loss,
                    'roi' => $subCampaign->roi,
                    'win_rate' => $subCampaign->win_rate,
                    'total_bets' => $subCampaign->total_bets,
                    'updated_at' => $subCampaign->updated_at->timestamp
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $performanceData,
                'timestamp' => now()->timestamp
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate alerts for sub-campaigns
     */
    protected function generateAlerts(Campaign $campaign, array $aggregation): array
    {
        $alerts = [];

        // Check for low balance alerts
        $lowBalanceSubCampaigns = $campaign->subCampaigns()
            ->where('status', 'active')
            ->whereColumn('current_balance', '<', DB::raw('allocated_balance * 0.1'))
            ->get();

        foreach ($lowBalanceSubCampaigns as $subCampaign) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Số dư thấp',
                'message' => "Sub-campaign '{$subCampaign->name}' chỉ còn " . number_format($subCampaign->current_balance) . " ₫",
                'sub_campaign_id' => $subCampaign->id
            ];
        }

        // Check for poor performance
        $poorPerformers = $campaign->subCampaigns()
            ->where('status', 'active')
            ->where('total_bets', '>', 10)
            ->get()
            ->filter(function ($subCampaign) {
                return $subCampaign->win_rate < 30;
            });

        foreach ($poorPerformers as $subCampaign) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Performance kém',
                'message' => "Sub-campaign '{$subCampaign->name}' có tỷ lệ thắng chỉ " . number_format($subCampaign->win_rate, 1) . "%",
                'sub_campaign_id' => $subCampaign->id
            ];
        }

        // Check for auto-stop candidates
        $autoStopCandidates = $campaign->subCampaigns()
            ->where('status', 'active')
            ->get()
            ->filter(function ($subCampaign) {
                return $subCampaign->shouldAutoStop();
            });

        foreach ($autoStopCandidates as $subCampaign) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Sẵn sàng auto-stop',
                'message' => "Sub-campaign '{$subCampaign->name}' đáp ứng điều kiện auto-stop",
                'sub_campaign_id' => $subCampaign->id
            ];
        }

        return $alerts;
    }
}
