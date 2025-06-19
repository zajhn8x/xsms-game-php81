# Tạo và Cấu Hình Chiến Dịch Đặt Cược

## Mục tiêu
Xây dựng giao diện và logic cho phép người dùng tạo, cấu hình và quản lý các chiến dịch đặt cược với nhiều tùy chọn linh hoạt.

## Prerequisites
- User authentication đã hoạt động
- Hệ thống phân quyền đã được thiết lập
- Database schema cho campaigns đã tồn tại

## Tính năng Cần Xây Dựng

### 1. Campaign Types (Loại chiến dịch)
- **Manual Campaign**: Đặt cược thủ công
- **Auto Heatmap Campaign**: Đặt cược tự động dựa trên heatmap
- **Auto Streak Campaign**: Đặt cược tự động dựa trên chuỗi số
- **Hybrid Campaign**: Kết hợp manual và auto

### 2. Campaign Configuration
- **Thời gian**: Ngày bắt đầu, kết thúc, số ngày chạy
- **Tài chính**: Số dư ban đầu, giới hạn cược, stop loss/take profit
- **Chiến lược**: Thuật toán đặt cược, tham số cấu hình
- **Rủi ro**: Giới hạn thua lỗ, số cược tối đa/ngày

## Các Bước Thực Hiện

### Bước 1: Cập nhật Campaign Model
```php
// app/Models/Campaign.php
class Campaign extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'campaign_type',
        'start_date', 'end_date', 'days', 
        'initial_balance', 'current_balance', 'target_profit',
        'daily_bet_limit', 'max_loss_per_day', 'total_loss_limit',
        'auto_stop_loss', 'auto_take_profit', 'stop_loss_amount', 'take_profit_amount',
        'betting_strategy', 'strategy_config',
        'is_public', 'status', 'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'strategy_config' => 'array',
        'auto_stop_loss' => 'boolean',
        'auto_take_profit' => 'boolean',
        'is_public' => 'boolean'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bets()
    {
        return $this->hasMany(CampaignBet::class);
    }

    // Computed attributes
    public function getProfitAttribute()
    {
        return $this->current_balance - $this->initial_balance;
    }

    public function getProfitPercentageAttribute()
    {
        return $this->initial_balance > 0 
            ? round(($this->profit / $this->initial_balance) * 100, 2) 
            : 0;
    }

    public function getWinRateAttribute()
    {
        $totalBets = $this->bets()->count();
        if ($totalBets === 0) return 0;
        
        $winBets = $this->bets()->where('is_win', true)->count();
        return round(($winBets / $totalBets) * 100, 2);
    }

    public function getDaysRunningAttribute()
    {
        if ($this->status === 'pending') return 0;
        
        $startDate = $this->start_date;
        $endDate = $this->status === 'completed' ? $this->end_date : now();
        
        return $startDate->diffInDays($endDate) + 1;
    }
}
```

### Bước 2: Tạo Campaign Configuration Service
```php
// app/Services/CampaignConfigurationService.php
class CampaignConfigurationService
{
    public function getAvailableStrategies()
    {
        return [
            'manual' => [
                'name' => 'Đặt cược thủ công',
                'description' => 'Bạn tự quyết định số cược mỗi ngày',
                'config_fields' => []
            ],
            'auto_heatmap' => [
                'name' => 'Tự động theo Heatmap',
                'description' => 'Đặt cược dựa trên phân tích heatmap',
                'config_fields' => [
                    'min_heat_score' => 'Điểm heat tối thiểu',
                    'max_numbers_per_day' => 'Số lượng số tối đa/ngày',
                    'base_bet_amount' => 'Tiền cược cơ bản',
                    'progressive_betting' => 'Tăng cược theo chuỗi'
                ]
            ],
            'auto_streak' => [
                'name' => 'Tự động theo Chuỗi số',
                'description' => 'Đặt cược dựa trên chuỗi số không về',
                'config_fields' => [
                    'min_streak_days' => 'Số ngày streak tối thiểu',
                    'max_streak_days' => 'Số ngày streak tối đa',
                    'base_bet_amount' => 'Tiền cược cơ bản',
                    'multiplier_per_day' => 'Hệ số nhân theo ngày'
                ]
            ],
            'hybrid' => [
                'name' => 'Kết hợp Manual + Auto',
                'description' => 'Gợi ý tự động, bạn quyết định cuối cùng',
                'config_fields' => [
                    'auto_suggest' => 'Gợi ý tự động',
                    'require_confirmation' => 'Yêu cầu xác nhận'
                ]
            ]
        ];
    }

    public function validateConfiguration(array $config, string $strategy)
    {
        $strategies = $this->getAvailableStrategies();
        
        if (!isset($strategies[$strategy])) {
            throw new \InvalidArgumentException('Invalid strategy');
        }

        $rules = $this->getValidationRules($strategy);
        
        return validator($config, $rules)->validate();
    }

    private function getValidationRules(string $strategy)
    {
        $commonRules = [
            'initial_balance' => 'required|numeric|min:100000',
            'daily_bet_limit' => 'nullable|numeric|min:10000',
            'max_loss_per_day' => 'nullable|numeric|min:0',
            'total_loss_limit' => 'nullable|numeric|min:0'
        ];

        $strategyRules = [
            'auto_heatmap' => [
                'strategy_config.min_heat_score' => 'required|numeric|min:0|max:100',
                'strategy_config.max_numbers_per_day' => 'required|integer|min:1|max:10',
                'strategy_config.base_bet_amount' => 'required|numeric|min:10000',
                'strategy_config.progressive_betting' => 'boolean'
            ],
            'auto_streak' => [
                'strategy_config.min_streak_days' => 'required|integer|min:1|max:30',
                'strategy_config.max_streak_days' => 'required|integer|min:1|max:100',
                'strategy_config.base_bet_amount' => 'required|numeric|min:10000',
                'strategy_config.multiplier_per_day' => 'required|numeric|min:1|max:5'
            ]
        ];

        return array_merge($commonRules, $strategyRules[$strategy] ?? []);
    }
}
```

### Bước 3: Cập nhật Campaign Controller
```php
// app/Http/Controllers/CampaignController.php
class CampaignController extends Controller
{
    protected $campaignService;
    protected $configService;

    public function __construct(
        CampaignService $campaignService,
        CampaignConfigurationService $configService
    ) {
        $this->campaignService = $campaignService;
        $this->configService = $configService;
        $this->middleware('auth');
        $this->middleware('permission:create_campaign')->only(['create', 'store']);
    }

    public function create()
    {
        $strategies = $this->configService->getAvailableStrategies();
        
        return view('campaigns.create', compact('strategies'));
    }

    public function store(Request $request)
    {
        // Check campaign limits for non-premium users
        if (!auth()->user()->can('unlimited_campaigns')) {
            $activeCampaigns = auth()->user()->campaigns()
                ->whereIn('status', ['active', 'running'])
                ->count();
                
            if ($activeCampaigns >= 3) {
                return back()->with('error', 'Bạn đã đạt giới hạn chiến dịch đang hoạt động. Nâng cấp Premium để tạo thêm.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'campaign_type' => 'required|in:live,historical',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'days' => 'nullable|integer|min:1|max:365',
            'initial_balance' => 'required|numeric|min:100000',
            'target_profit' => 'nullable|numeric|min:0',
            'daily_bet_limit' => 'nullable|numeric|min:10000',
            'max_loss_per_day' => 'nullable|numeric|min:0',
            'total_loss_limit' => 'nullable|numeric|min:0',
            'auto_stop_loss' => 'boolean',
            'auto_take_profit' => 'boolean',
            'stop_loss_amount' => 'nullable|numeric|min:0',
            'take_profit_amount' => 'nullable|numeric|min:0',
            'betting_strategy' => 'required|string',
            'strategy_config' => 'nullable|array',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:2000'
        ]);

        // Validate strategy configuration
        if ($validated['strategy_config']) {
            $validated['strategy_config'] = $this->configService->validateConfiguration(
                $validated['strategy_config'], 
                $validated['betting_strategy']
            );
        }

        // Calculate end date if days is provided
        if ($validated['days'] && !$validated['end_date']) {
            $validated['end_date'] = \Carbon\Carbon::parse($validated['start_date'])
                ->addDays($validated['days'] - 1);
        }

        // Set defaults
        $validated['user_id'] = auth()->id();
        $validated['current_balance'] = $validated['initial_balance'];
        $validated['status'] = 'pending';

        $campaign = Campaign::create($validated);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Tạo chiến dịch thành công! Bạn có thể bắt đầu chạy ngay.');
    }

    public function getStrategyConfig(Request $request)
    {
        $strategy = $request->get('strategy');
        $strategies = $this->configService->getAvailableStrategies();
        
        if (!isset($strategies[$strategy])) {
            return response()->json(['error' => 'Invalid strategy'], 400);
        }

        return response()->json([
            'config_fields' => $strategies[$strategy]['config_fields']
        ]);
    }
}
```

### Bước 4: Tạo Form Wizard cho Campaign Creation
```blade
{{-- resources/views/campaigns/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4>Tạo Chiến Dịch Đặt Cược Mới</h4>
                    
                    {{-- Progress Steps --}}
                    <div class="progress-steps mt-3">
                        <div class="step active" data-step="1">
                            <span class="step-number">1</span>
                            <span class="step-title">Thông tin cơ bản</span>
                        </div>
                        <div class="step" data-step="2">
                            <span class="step-number">2</span>
                            <span class="step-title">Chiến lược</span>
                        </div>
                        <div class="step" data-step="3">
                            <span class="step-number">3</span>
                            <span class="step-title">Quản lý rủi ro</span>
                        </div>
                        <div class="step" data-step="4">
                            <span class="step-number">4</span>
                            <span class="step-title">Xác nhận</span>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('campaigns.store') }}" id="campaignForm">
                        @csrf

                        {{-- Step 1: Basic Information --}}
                        <div class="form-step active" data-step="1">
                            <h5 class="mb-3">Thông tin cơ bản</h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Tên chiến dịch *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="campaign_type" class="form-label">Loại chiến dịch *</label>
                                        <select class="form-select" id="campaign_type" name="campaign_type" required>
                                            <option value="">Chọn loại chiến dịch</option>
                                            <option value="live" {{ old('campaign_type') === 'live' ? 'selected' : '' }}>
                                                Chiến dịch trực tiếp (Live)
                                            </option>
                                            <option value="historical" {{ old('campaign_type') === 'historical' ? 'selected' : '' }}>
                                                Kiểm thử lịch sử (Historical)
                                            </option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="initial_balance" class="form-label">Số dư ban đầu (VNĐ) *</label>
                                        <input type="number" class="form-control" id="initial_balance" 
                                               name="initial_balance" value="{{ old('initial_balance', 1000000) }}" 
                                               min="100000" step="10000" required>
                                        <div class="form-text">Tối thiểu 100,000 VNĐ</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Mô tả</label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="3">{{ old('description') }}</textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="start_date" class="form-label">Ngày bắt đầu *</label>
                                                <input type="date" class="form-control" id="start_date" 
                                                       name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" 
                                                       min="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="days" class="form-label">Số ngày chạy</label>
                                                <input type="number" class="form-control" id="days" 
                                                       name="days" value="{{ old('days', 30) }}" 
                                                       min="1" max="365">
                                                <div class="form-text">Để trống nếu chạy không giới hạn</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="is_public" 
                                                   name="is_public" value="1" {{ old('is_public') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_public">
                                                Chia sẻ công khai
                                            </label>
                                            <div class="form-text">Cho phép người khác xem kết quả chiến dịch</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Step 2: Strategy Configuration --}}
                        <div class="form-step" data-step="2">
                            <h5 class="mb-3">Cấu hình chiến lược đặt cược</h5>

                            <div class="mb-4">
                                <label class="form-label">Chọn chiến lược *</label>
                                <div class="row">
                                    @foreach($strategies as $key => $strategy)
                                    <div class="col-md-6 mb-3">
                                        <div class="card strategy-card" data-strategy="{{ $key }}">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input type="radio" class="form-check-input" 
                                                           id="strategy_{{ $key }}" name="betting_strategy" 
                                                           value="{{ $key }}" 
                                                           {{ old('betting_strategy') === $key ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="strategy_{{ $key }}">
                                                        <h6>{{ $strategy['name'] }}</h6>
                                                        <p class="text-muted mb-0">{{ $strategy['description'] }}</p>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Strategy Configuration Fields (Dynamic) --}}
                            <div id="strategyConfig" class="mb-4">
                                {{-- Will be populated by JavaScript --}}
                            </div>
                        </div>

                        {{-- Step 3: Risk Management --}}
                        <div class="form-step" data-step="3">
                            <h5 class="mb-3">Quản lý rủi ro</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="daily_bet_limit" class="form-label">Giới hạn cược/ngày (VNĐ)</label>
                                        <input type="number" class="form-control" id="daily_bet_limit" 
                                               name="daily_bet_limit" value="{{ old('daily_bet_limit') }}" 
                                               min="10000" step="10000">
                                        <div class="form-text">Để trống nếu không giới hạn</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="max_loss_per_day" class="form-label">Giới hạn thua/ngày (VNĐ)</label>
                                        <input type="number" class="form-control" id="max_loss_per_day" 
                                               name="max_loss_per_day" value="{{ old('max_loss_per_day') }}" 
                                               min="0" step="10000">
                                    </div>

                                    <div class="mb-3">
                                        <label for="total_loss_limit" class="form-label">Giới hạn thua tổng (VNĐ)</label>
                                        <input type="number" class="form-control" id="total_loss_limit" 
                                               name="total_loss_limit" value="{{ old('total_loss_limit') }}" 
                                               min="0" step="10000">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="auto_stop_loss" 
                                                   name="auto_stop_loss" value="1" {{ old('auto_stop_loss') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="auto_stop_loss">
                                                Tự động dừng lỗ (Stop Loss)
                                            </label>
                                        </div>
                                        <div class="mt-2 stop-loss-config" style="display: none;">
                                            <input type="number" class="form-control" id="stop_loss_amount" 
                                                   name="stop_loss_amount" placeholder="Số tiền dừng lỗ" 
                                                   value="{{ old('stop_loss_amount') }}" min="0" step="10000">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="auto_take_profit" 
                                                   name="auto_take_profit" value="1" {{ old('auto_take_profit') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="auto_take_profit">
                                                Tự động chốt lời (Take Profit)
                                            </label>
                                        </div>
                                        <div class="mt-2 take-profit-config" style="display: none;">
                                            <input type="number" class="form-control" id="take_profit_amount" 
                                                   name="take_profit_amount" placeholder="Số tiền chốt lời" 
                                                   value="{{ old('take_profit_amount') }}" min="0" step="10000">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="target_profit" class="form-label">Mục tiêu lợi nhuận (VNĐ)</label>
                                        <input type="number" class="form-control" id="target_profit" 
                                               name="target_profit" value="{{ old('target_profit') }}" 
                                               min="0" step="10000">
                                        <div class="form-text">Mục tiêu lợi nhuận muốn đạt được</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Ghi chú</label>
                                <textarea class="form-control" id="notes" name="notes" 
                                          rows="3" placeholder="Ghi chú về chiến dịch...">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        {{-- Step 4: Confirmation --}}
                        <div class="form-step" data-step="4">
                            <h5 class="mb-3">Xác nhận thông tin</h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>Thông tin chiến dịch</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="summary-item">
                                                <strong>Tên:</strong> <span id="summary-name">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <strong>Loại:</strong> <span id="summary-type">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <strong>Số dư ban đầu:</strong> <span id="summary-balance">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <strong>Thời gian:</strong> <span id="summary-duration">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <strong>Chiến lược:</strong> <span id="summary-strategy">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>Quản lý rủi ro</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="summary-item">
                                                <strong>Giới hạn cược/ngày:</strong> <span id="summary-daily-limit">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <strong>Tự động dừng lỗ:</strong> <span id="summary-stop-loss">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <strong>Tự động chốt lời:</strong> <span id="summary-take-profit">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <strong>Mục tiêu lợi nhuận:</strong> <span id="summary-target">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3">
                                <h6>📋 Lưu ý quan trọng:</h6>
                                <ul class="mb-0">
                                    <li>Chiến dịch sau khi tạo sẽ ở trạng thái "Pending" và cần được kích hoạt</li>
                                    <li>Bạn có thể chỉnh sửa cấu hình trước khi kích hoạt chiến dịch</li>
                                    <li>Các giới hạn rủi ro sẽ được áp dụng tự động khi chiến dịch chạy</li>
                                    <li>Chiến dịch công khai có thể được xem bởi người dùng khác</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Navigation Buttons --}}
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">
                                Quay lại
                            </button>
                            <button type="button" class="btn btn-primary" id="nextBtn">
                                Tiếp theo
                            </button>
                            <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                                Tạo chiến dịch
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.progress-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0;
}

.step {
    flex: 1;
    text-align: center;
    position: relative;
    padding: 10px;
}

.step::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 50%;
    right: -50%;
    height: 2px;
    background: #dee2e6;
    z-index: 1;
}

.step:last-child::before {
    display: none;
}

.step.active::before,
.step.completed::before {
    background: #007bff;
}

.step-number {
    display: inline-block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    border-radius: 50%;
    background: #dee2e6;
    color: #6c757d;
    font-weight: bold;
    position: relative;
    z-index: 2;
}

.step.active .step-number,
.step.completed .step-number {
    background: #007bff;
    color: white;
}

.step-title {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #6c757d;
}

.step.active .step-title,
.step.completed .step-title {
    color: #007bff;
    font-weight: 500;
}

.form-step {
    display: none;
}

.form-step.active {
    display: block;
}

.strategy-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.strategy-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 8px rgba(0,123,255,0.1);
}

.strategy-card.selected {
    border-color: #007bff;
    background-color: #f8f9ff;
}

.summary-item {
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f1f1;
}

.summary-item:last-child {
    border-bottom: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    const totalSteps = 4;

    // Step navigation
    function showStep(step) {
        // Hide all steps
        document.querySelectorAll('.form-step').forEach(el => {
            el.classList.remove('active');
        });
        
        // Show current step
        document.querySelector(`[data-step="${step}"]`).classList.add('active');
        
        // Update progress
        document.querySelectorAll('.step').forEach((el, index) => {
            el.classList.remove('active', 'completed');
            if (index + 1 < step) {
                el.classList.add('completed');
            } else if (index + 1 === step) {
                el.classList.add('active');
            }
        });
        
        // Update buttons
        document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'inline-block';
        document.getElementById('nextBtn').style.display = step === totalSteps ? 'none' : 'inline-block';
        document.getElementById('submitBtn').style.display = step === totalSteps ? 'inline-block' : 'none';
    }

    // Next button
    document.getElementById('nextBtn').addEventListener('click', function() {
        if (validateStep(currentStep)) {
            currentStep++;
            showStep(currentStep);
            
            if (currentStep === 4) {
                updateSummary();
            }
        }
    });

    // Previous button
    document.getElementById('prevBtn').addEventListener('click', function() {
        currentStep--;
        showStep(currentStep);
    });

    // Strategy selection
    document.querySelectorAll('input[name="betting_strategy"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Update card selection
            document.querySelectorAll('.strategy-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            this.closest('.strategy-card').classList.add('selected');
            
            // Load strategy configuration
            loadStrategyConfig(this.value);
        });
    });

    // Stop loss / Take profit toggles
    document.getElementById('auto_stop_loss').addEventListener('change', function() {
        document.querySelector('.stop-loss-config').style.display = 
            this.checked ? 'block' : 'none';
    });

    document.getElementById('auto_take_profit').addEventListener('change', function() {
        document.querySelector('.take-profit-config').style.display = 
            this.checked ? 'block' : 'none';
    });

    function validateStep(step) {
        const stepElement = document.querySelector(`[data-step="${step}"]`);
        const requiredFields = stepElement.querySelectorAll('[required]');
        
        for (let field of requiredFields) {
            if (!field.value.trim()) {
                field.focus();
                field.classList.add('is-invalid');
                return false;
            }
            field.classList.remove('is-invalid');
        }
        
        return true;
    }

    function loadStrategyConfig(strategy) {
        fetch(`/campaigns/strategy-config?strategy=${strategy}`)
            .then(response => response.json())
            .then(data => {
                const configDiv = document.getElementById('strategyConfig');
                configDiv.innerHTML = '';
                
                if (data.config_fields && Object.keys(data.config_fields).length > 0) {
                    configDiv.innerHTML = '<h6>Cấu hình chiến lược</h6>';
                    
                    for (const [field, label] of Object.entries(data.config_fields)) {
                        configDiv.innerHTML += `
                            <div class="mb-3">
                                <label for="strategy_${field}" class="form-label">${label}</label>
                                <input type="text" class="form-control" id="strategy_${field}" 
                                       name="strategy_config[${field}]" 
                                       value="{{ old('strategy_config.${field}') }}">
                            </div>
                        `;
                    }
                }
            });
    }

    function updateSummary() {
        document.getElementById('summary-name').textContent = 
            document.getElementById('name').value || '-';
            
        const campaignType = document.getElementById('campaign_type').value;
        document.getElementById('summary-type').textContent = 
            campaignType === 'live' ? 'Chiến dịch trực tiếp' : 
            campaignType === 'historical' ? 'Kiểm thử lịch sử' : '-';
            
        const balance = document.getElementById('initial_balance').value;
        document.getElementById('summary-balance').textContent = 
            balance ? new Intl.NumberFormat('vi-VN').format(balance) + ' VNĐ' : '-';
            
        const startDate = document.getElementById('start_date').value;
        const days = document.getElementById('days').value;
        document.getElementById('summary-duration').textContent = 
            startDate ? `${startDate}${days ? ` (${days} ngày)` : ' (không giới hạn)'}` : '-';
            
        const strategy = document.querySelector('input[name="betting_strategy"]:checked');
        document.getElementById('summary-strategy').textContent = 
            strategy ? strategy.closest('.strategy-card').querySelector('h6').textContent : '-';
            
        const dailyLimit = document.getElementById('daily_bet_limit').value;
        document.getElementById('summary-daily-limit').textContent = 
            dailyLimit ? new Intl.NumberFormat('vi-VN').format(dailyLimit) + ' VNĐ' : 'Không giới hạn';
            
        const stopLoss = document.getElementById('auto_stop_loss').checked;
        const stopLossAmount = document.getElementById('stop_loss_amount').value;
        document.getElementById('summary-stop-loss').textContent = 
            stopLoss ? (stopLossAmount ? new Intl.NumberFormat('vi-VN').format(stopLossAmount) + ' VNĐ' : 'Có') : 'Không';
            
        const takeProfit = document.getElementById('auto_take_profit').checked;
        const takeProfitAmount = document.getElementById('take_profit_amount').value;
        document.getElementById('summary-take-profit').textContent = 
            takeProfit ? (takeProfitAmount ? new Intl.NumberFormat('vi-VN').format(takeProfitAmount) + ' VNĐ' : 'Có') : 'Không';
            
        const target = document.getElementById('target_profit').value;
        document.getElementById('summary-target').textContent = 
            target ? new Intl.NumberFormat('vi-VN').format(target) + ' VNĐ' : 'Không đặt';
    }

    // Initialize
    showStep(1);
    
    // Trigger change event for initially selected strategy
    const selectedStrategy = document.querySelector('input[name="betting_strategy"]:checked');
    if (selectedStrategy) {
        selectedStrategy.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
```

### Bước 5: Tạo Routes cho Strategy Config
```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::resource('campaigns', CampaignController::class);
    Route::get('campaigns/strategy-config', [CampaignController::class, 'getStrategyConfig'])
        ->name('campaigns.strategy-config');
});
```

## Testing

### Feature Tests
```php
// tests/Feature/CampaignCreationTest.php
class CampaignCreationTest extends TestCase
{
    public function test_user_can_create_manual_campaign()
    {
        $user = User::factory()->create();
        $user->assignRole('basic_user');
        
        $response = $this->actingAs($user)
            ->post('/campaigns', [
                'name' => 'Test Campaign',
                'campaign_type' => 'live',
                'start_date' => now()->addDay()->format('Y-m-d'),
                'initial_balance' => 1000000,
                'betting_strategy' => 'manual',
                'is_public' => true
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'user_id' => $user->id,
            'status' => 'pending'
        ]);
    }

    public function test_basic_user_cannot_create_more_than_limit()
    {
        $user = User::factory()->create();
        $user->assignRole('basic_user');
        
        // Create 3 active campaigns (limit for basic users)
        Campaign::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);
        
        $response = $this->actingAs($user)
            ->post('/campaigns', [
                'name' => 'Fourth Campaign',
                'campaign_type' => 'live',
                'start_date' => now()->addDay()->format('Y-m-d'),
                'initial_balance' => 1000000,
                'betting_strategy' => 'manual'
            ]);

        $response->assertSessionHas('error');
    }
}
```

## Performance & UX Considerations

1. **Progressive Enhancement**: Form hoạt động tốt cả khi JavaScript bị tắt
2. **Real-time Validation**: Validate từng step để cải thiện UX
3. **Auto-save Draft**: Lưu form draft để tránh mất dữ liệu
4. **Responsive Design**: Đảm bảo form hoạt động tốt trên mobile

## Security

1. **Input Validation**: Validate tất cả input ở cả client và server
2. **Authorization**: Kiểm tra quyền tạo campaign và giới hạn cho từng loại user
3. **Rate Limiting**: Giới hạn số campaign có thể tạo trong một khoảng thời gian
4. **Data Sanitization**: Làm sạch tất cả input trước khi lưu database
