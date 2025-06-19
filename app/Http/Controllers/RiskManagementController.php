<?php

namespace App\Http\Controllers;

use App\Models\RiskManagementRule;
use App\Services\RiskManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RiskManagementController extends Controller
{
    protected $riskService;

    public function __construct(RiskManagementService $riskService)
    {
        $this->riskService = $riskService;
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        $riskRules = $user->riskRules()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $defaultRules = $this->riskService->getDefaultRiskRules();

        return view('risk-management.index', [
            'risk_rules' => $riskRules,
            'default_rules' => $defaultRules,
            'user' => $user
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'rule_name' => 'required|string|max:255',
            'rule_type' => 'required|string|in:daily_loss_limit,consecutive_loss_limit,win_streak_protection,balance_threshold',
            'conditions' => 'required|array',
            'actions' => 'required|array',
            'threshold_amount' => 'nullable|numeric|min:0',
            'threshold_count' => 'nullable|integer|min:0',
            'time_window_hours' => 'nullable|integer|min:1|max:168', // Max 1 week
            'is_global' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        $user = auth()->user();

        $riskRule = $this->riskService->createRiskRule($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Risk rule đã được tạo thành công',
            'risk_rule' => $riskRule
        ]);
    }

    public function show(RiskManagementRule $riskRule)
    {
        $this->authorize('view', $riskRule);

        return view('risk-management.show', [
            'risk_rule' => $riskRule
        ]);
    }

    public function update(Request $request, RiskManagementRule $riskRule): JsonResponse
    {
        $this->authorize('update', $riskRule);

        $request->validate([
            'rule_name' => 'required|string|max:255',
            'conditions' => 'required|array',
            'actions' => 'required|array',
            'threshold_amount' => 'nullable|numeric|min:0',
            'threshold_count' => 'nullable|integer|min:0',
            'time_window_hours' => 'nullable|integer|min:1|max:168',
            'is_active' => 'boolean',
            'is_global' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        $riskRule->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Risk rule đã được cập nhật',
            'risk_rule' => $riskRule
        ]);
    }

    public function destroy(RiskManagementRule $riskRule): JsonResponse
    {
        $this->authorize('delete', $riskRule);

        $riskRule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Risk rule đã được xóa'
        ]);
    }

    public function toggle(RiskManagementRule $riskRule): JsonResponse
    {
        $this->authorize('update', $riskRule);

        $riskRule->update(['is_active' => !$riskRule->is_active]);

        return response()->json([
            'success' => true,
            'message' => $riskRule->is_active ? 'Risk rule đã được kích hoạt' : 'Risk rule đã được tắt',
            'is_active' => $riskRule->is_active
        ]);
    }

    public function setupDefaults(): JsonResponse
    {
        $user = auth()->user();

        // Check if user already has default rules
        if ($user->riskRules()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'User đã có risk rules'
            ]);
        }

        $this->riskService->setupDefaultRiskRules($user);

        return response()->json([
            'success' => true,
            'message' => 'Default risk rules đã được thiết lập'
        ]);
    }

    public function checkRisk(): JsonResponse
    {
        $user = auth()->user();

        $riskStatus = $this->riskService->checkUserRisk($user);

        return response()->json([
            'is_safe' => $riskStatus,
            'message' => $riskStatus ? 'Không có risk được phát hiện' : 'Risk được phát hiện - một số campaigns có thể bị tạm dừng'
        ]);
    }

    // API endpoints for AJAX
    public function ruleTypes(): JsonResponse
    {
        $ruleTypes = [
            'daily_loss_limit' => [
                'name' => 'Giới hạn thua hàng ngày',
                'description' => 'Tạm dừng trading khi thua quá số tiền quy định trong ngày',
                'condition_fields' => ['daily_loss_amount'],
                'action_options' => ['pause_campaigns', 'send_notification', 'limit_daily_betting']
            ],
            'consecutive_loss_limit' => [
                'name' => 'Giới hạn thua liên tiếp',
                'description' => 'Giảm cược hoặc tạm dừng khi thua liên tiếp nhiều lần',
                'condition_fields' => ['consecutive_losses'],
                'action_options' => ['reduce_bet_amounts', 'pause_campaigns', 'send_notification']
            ],
            'win_streak_protection' => [
                'name' => 'Bảo vệ chuỗi thắng',
                'description' => 'Bảo vệ lợi nhuận khi thắng liên tiếp nhiều lần',
                'condition_fields' => ['win_streak_count'],
                'action_options' => ['reduce_bet_amounts', 'send_notification']
            ],
            'balance_threshold' => [
                'name' => 'Ngưỡng số dư tối thiểu',
                'description' => 'Tạm dừng trading khi số dư thấp hơn ngưỡng an toàn',
                'condition_fields' => ['balance_threshold'],
                'action_options' => ['pause_campaigns', 'send_notification']
            ]
        ];

        return response()->json(['rule_types' => $ruleTypes]);
    }

    public function ruleTemplates(): JsonResponse
    {
        $templates = [
            'conservative' => [
                'name' => 'Bảo thủ',
                'rules' => [
                    [
                        'rule_name' => 'Giới hạn thua ngày (Bảo thủ)',
                        'rule_type' => 'daily_loss_limit',
                        'conditions' => [['type' => 'daily_loss_amount', 'value' => 200000]],
                        'actions' => [
                            ['type' => 'pause_campaigns', 'params' => []],
                            ['type' => 'send_notification', 'params' => ['message' => 'Đã đạt giới hạn thua ngày']]
                        ],
                        'threshold_amount' => 200000,
                        'time_window_hours' => 24
                    ]
                ]
            ],
            'moderate' => [
                'name' => 'Vừa phải',
                'rules' => [
                    [
                        'rule_name' => 'Giới hạn thua ngày (Vừa phải)',
                        'rule_type' => 'daily_loss_limit',
                        'conditions' => [['type' => 'daily_loss_amount', 'value' => 500000]],
                        'actions' => [
                            ['type' => 'pause_campaigns', 'params' => []],
                            ['type' => 'send_notification', 'params' => ['message' => 'Đã đạt giới hạn thua ngày']]
                        ],
                        'threshold_amount' => 500000,
                        'time_window_hours' => 24
                    ]
                ]
            ],
            'aggressive' => [
                'name' => 'Tích cực',
                'rules' => [
                    [
                        'rule_name' => 'Giới hạn thua ngày (Tích cực)',
                        'rule_type' => 'daily_loss_limit',
                        'conditions' => [['type' => 'daily_loss_amount', 'value' => 1000000]],
                        'actions' => [
                            ['type' => 'send_notification', 'params' => ['message' => 'Cảnh báo: đã thua nhiều trong ngày']]
                        ],
                        'threshold_amount' => 1000000,
                        'time_window_hours' => 24
                    ]
                ]
            ]
        ];

        return response()->json(['templates' => $templates]);
    }

    public function statistics(): JsonResponse
    {
        $user = auth()->user();

        $stats = [
            'total_rules' => $user->riskRules()->count(),
            'active_rules' => $user->riskRules()->where('is_active', true)->count(),
            'triggered_today' => $user->riskRules()
                ->whereDate('last_triggered_at', today())
                ->count(),
            'most_triggered' => $user->riskRules()
                ->orderBy('trigger_count', 'desc')
                ->first(),
            'recent_triggers' => $user->riskRules()
                ->where('last_triggered_at', '>=', now()->subDays(7))
                ->orderBy('last_triggered_at', 'desc')
                ->limit(5)
                ->get()
        ];

        return response()->json(['statistics' => $stats]);
    }
}
