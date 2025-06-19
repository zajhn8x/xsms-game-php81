# Campaign Templates System

## Tổng quan
Xây dựng hệ thống template cho các chiến dịch đặt cược để người dùng có thể tạo nhanh các chiến dịch từ các mẫu có sẵn.

## Mục tiêu
- Tạo templates cho các loại chiến dịch phổ biến
- Cho phép người dùng tạo custom templates
- Chia sẻ templates giữa người dùng
- Quản lý phiên bản template
- Import/Export templates

## Database Schema

### Campaign Templates Table
```sql
CREATE TABLE campaign_templates (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('system', 'user', 'shared') DEFAULT 'user',
    user_id BIGINT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    template_data JSON NOT NULL,
    usage_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_public (is_public),
    INDEX idx_user (user_id)
);
```

### Template Ratings Table
```sql
CREATE TABLE template_ratings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    template_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (template_id) REFERENCES campaign_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_template (user_id, template_id)
);
```

## Implementation Steps

### Step 1: CampaignTemplate Model
```php
// app/Models/CampaignTemplate.php
class CampaignTemplate extends Model
{
    protected $fillable = [
        'name', 'description', 'category', 'user_id', 
        'is_public', 'template_data', 'usage_count', 'rating'
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_public' => 'boolean',
        'rating' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ratings()
    {
        return $this->hasMany(TemplateRating::class, 'template_id');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'template_id');
    }

    // Scope for public templates
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // Scope for system templates
    public function scopeSystem($query)
    {
        return $query->where('category', 'system');
    }

    // Scope for user's own templates
    public function scopeOwn($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Get template with validation
    public function getValidatedTemplateData(): array
    {
        $data = $this->template_data;
        
        // Validate and set defaults
        return [
            'campaign_type' => $data['campaign_type'] ?? 'live',
            'initial_balance' => $data['initial_balance'] ?? 1000000,
            'daily_bet_limit' => $data['daily_bet_limit'] ?? null,
            'max_loss_per_day' => $data['max_loss_per_day'] ?? null,
            'total_loss_limit' => $data['total_loss_limit'] ?? null,
            'auto_stop_loss' => $data['auto_stop_loss'] ?? false,
            'auto_take_profit' => $data['auto_take_profit'] ?? false,
            'stop_loss_amount' => $data['stop_loss_amount'] ?? null,
            'take_profit_amount' => $data['take_profit_amount'] ?? null,
            'betting_strategy' => $data['betting_strategy'] ?? 'manual',
            'strategy_config' => $data['strategy_config'] ?? [],
            'bet_preferences' => $data['bet_preferences'] ?? [],
            'days' => $data['days'] ?? 30,
            'target_profit' => $data['target_profit'] ?? null
        ];
    }

    // Update usage count
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    // Update rating
    public function updateRating()
    {
        $avgRating = $this->ratings()->avg('rating');
        $this->update(['rating' => $avgRating ?? 0]);
    }
}
```

### Step 2: TemplateRating Model
```php
// app/Models/TemplateRating.php
class TemplateRating extends Model
{
    protected $fillable = ['template_id', 'user_id', 'rating', 'comment'];

    public function template()
    {
        return $this->belongsTo(CampaignTemplate::class, 'template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::created(function ($rating) {
            $rating->template->updateRating();
        });

        static::updated(function ($rating) {
            $rating->template->updateRating();
        });

        static::deleted(function ($rating) {
            $rating->template->updateRating();
        });
    }
}
```

### Step 3: Template Service
```php
// app/Services/CampaignTemplateService.php
class CampaignTemplateService
{
    public function getTemplates($userId, $filters = [])
    {
        $query = CampaignTemplate::with(['user', 'ratings']);

        // Apply filters
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['own']) && $filters['own']) {
            $query->own($userId);
        } else {
            // Show public templates and user's own templates
            $query->where(function ($q) use ($userId) {
                $q->where('is_public', true)
                  ->orWhere('user_id', $userId)
                  ->orWhere('category', 'system');
            });
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Sort by usage or rating
        $sortBy = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';
        
        return $query->orderBy($sortBy, $sortOrder)->paginate(12);
    }

    public function createTemplate($userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            // Validate template data
            $templateData = $this->validateTemplateData($data['template_data']);

            return CampaignTemplate::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => 'user',
                'user_id' => $userId,
                'is_public' => $data['is_public'] ?? false,
                'template_data' => $templateData
            ]);
        });
    }

    public function createFromCampaign($userId, Campaign $campaign, array $templateInfo)
    {
        $templateData = [
            'campaign_type' => $campaign->campaign_type,
            'initial_balance' => $campaign->initial_balance,
            'daily_bet_limit' => $campaign->daily_bet_limit,
            'max_loss_per_day' => $campaign->max_loss_per_day,
            'total_loss_limit' => $campaign->total_loss_limit,
            'auto_stop_loss' => $campaign->auto_stop_loss,
            'auto_take_profit' => $campaign->auto_take_profit,
            'stop_loss_amount' => $campaign->stop_loss_amount,
            'take_profit_amount' => $campaign->take_profit_amount,
            'betting_strategy' => $campaign->betting_strategy,
            'strategy_config' => $campaign->strategy_config,
            'bet_preferences' => $campaign->bet_preferences,
            'days' => $campaign->days,
            'target_profit' => $campaign->target_profit
        ];

        return $this->createTemplate($userId, [
            'name' => $templateInfo['name'],
            'description' => $templateInfo['description'] ?? null,
            'is_public' => $templateInfo['is_public'] ?? false,
            'template_data' => $templateData
        ]);
    }

    public function createCampaignFromTemplate($userId, CampaignTemplate $template, array $overrides = [])
    {
        $templateData = $template->getValidatedTemplateData();
        
        // Merge with overrides
        $campaignData = array_merge($templateData, $overrides, [
            'user_id' => $userId,
            'template_id' => $template->id,
            'current_balance' => $templateData['initial_balance'],
            'status' => 'pending'
        ]);

        // Remove null values
        $campaignData = array_filter($campaignData, function ($value) {
            return $value !== null;
        });

        $campaign = Campaign::create($campaignData);
        
        // Increment template usage
        $template->incrementUsage();

        return $campaign;
    }

    public function duplicateTemplate($userId, CampaignTemplate $template, $newName)
    {
        if (!$this->canAccessTemplate($userId, $template)) {
            throw new \Exception('Không có quyền truy cập template này');
        }

        return CampaignTemplate::create([
            'name' => $newName,
            'description' => $template->description,
            'category' => 'user',
            'user_id' => $userId,
            'is_public' => false,
            'template_data' => $template->template_data
        ]);
    }

    public function rateTemplate($userId, $templateId, $rating, $comment = null)
    {
        $template = CampaignTemplate::findOrFail($templateId);
        
        if (!$template->is_public && $template->user_id !== $userId) {
            throw new \Exception('Không thể đánh giá template này');
        }

        return TemplateRating::updateOrCreate(
            ['template_id' => $templateId, 'user_id' => $userId],
            ['rating' => $rating, 'comment' => $comment]
        );
    }

    public function exportTemplate(CampaignTemplate $template)
    {
        return [
            'name' => $template->name,
            'description' => $template->description,
            'template_data' => $template->template_data,
            'exported_at' => now()->toISOString(),
            'version' => '1.0'
        ];
    }

    public function importTemplate($userId, array $templateData)
    {
        // Validate import data
        if (!isset($templateData['template_data']) || !isset($templateData['name'])) {
            throw new \Exception('Dữ liệu template không hợp lệ');
        }

        return $this->createTemplate($userId, [
            'name' => $templateData['name'] . ' (Imported)',
            'description' => $templateData['description'] ?? 'Imported template',
            'is_public' => false,
            'template_data' => $templateData['template_data']
        ]);
    }

    protected function validateTemplateData(array $data)
    {
        $validator = Validator::make($data, [
            'campaign_type' => 'required|in:live,historical',
            'initial_balance' => 'required|numeric|min:100000',
            'daily_bet_limit' => 'nullable|numeric|min:0',
            'max_loss_per_day' => 'nullable|numeric|min:0',
            'total_loss_limit' => 'nullable|numeric|min:0',
            'auto_stop_loss' => 'boolean',
            'auto_take_profit' => 'boolean',
            'stop_loss_amount' => 'nullable|numeric|min:0',
            'take_profit_amount' => 'nullable|numeric|min:0',
            'betting_strategy' => 'required|string',
            'strategy_config' => 'nullable|array',
            'bet_preferences' => 'nullable|array',
            'days' => 'nullable|integer|min:1|max:365',
            'target_profit' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function canAccessTemplate($userId, CampaignTemplate $template)
    {
        return $template->is_public || 
               $template->user_id === $userId || 
               $template->category === 'system';
    }

    public function getSystemTemplates()
    {
        return [
            [
                'name' => 'Conservative Betting',
                'description' => 'Chiến lược đặt cược thận trọng với giới hạn thua thấp',
                'category' => 'system',
                'template_data' => [
                    'campaign_type' => 'live',
                    'initial_balance' => 1000000,
                    'daily_bet_limit' => 50000,
                    'max_loss_per_day' => 100000,
                    'total_loss_limit' => 300000,
                    'auto_stop_loss' => true,
                    'stop_loss_amount' => 300000,
                    'betting_strategy' => 'manual',
                    'days' => 30
                ]
            ],
            [
                'name' => 'Aggressive Growth',
                'description' => 'Chiến lược tăng trưởng tích cực với mục tiêu lợi nhuận cao',
                'category' => 'system',
                'template_data' => [
                    'campaign_type' => 'live',
                    'initial_balance' => 2000000,
                    'daily_bet_limit' => 200000,
                    'target_profit' => 1000000,
                    'auto_take_profit' => true,
                    'take_profit_amount' => 1000000,
                    'betting_strategy' => 'auto_heatmap',
                    'strategy_config' => [
                        'min_confidence' => 0.7,
                        'max_numbers_per_day' => 5
                    ],
                    'days' => 60
                ]
            ],
            [
                'name' => 'Balanced Strategy',
                'description' => 'Chiến lược cân bằng giữa rủi ro và lợi nhuận',
                'category' => 'system',
                'template_data' => [
                    'campaign_type' => 'live',
                    'initial_balance' => 1500000,
                    'daily_bet_limit' => 100000,
                    'max_loss_per_day' => 150000,
                    'total_loss_limit' => 500000,
                    'target_profit' => 500000,
                    'auto_stop_loss' => true,
                    'auto_take_profit' => true,
                    'stop_loss_amount' => 500000,
                    'take_profit_amount' => 500000,
                    'betting_strategy' => 'auto_streak',
                    'days' => 45
                ]
            ]
        ];
    }
}
```

### Step 4: Template Controller
```php
// app/Http/Controllers/CampaignTemplateController.php
class CampaignTemplateController extends Controller
{
    protected $templateService;

    public function __construct(CampaignTemplateService $templateService)
    {
        $this->templateService = $templateService;
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['category', 'search', 'sort', 'order', 'own']);
        $templates = $this->templateService->getTemplates(auth()->id(), $filters);

        return view('campaign-templates.index', compact('templates', 'filters'));
    }

    public function create()
    {
        return view('campaign-templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'template_data' => 'required|array'
        ]);

        try {
            $template = $this->templateService->createTemplate(
                auth()->id(),
                $request->all()
            );

            return redirect()->route('campaign-templates.show', $template->id)
                ->with('success', 'Template đã được tạo thành công');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi tạo template: ' . $e->getMessage())->withInput();
        }
    }

    public function show(CampaignTemplate $template)
    {
        if (!$this->templateService->canAccessTemplate(auth()->id(), $template)) {
            abort(403);
        }

        $template->load(['user', 'ratings.user']);
        $userRating = $template->ratings()->where('user_id', auth()->id())->first();

        return view('campaign-templates.show', compact('template', 'userRating'));
    }

    public function edit(CampaignTemplate $template)
    {
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        return view('campaign-templates.edit', compact('template'));
    }

    public function update(Request $request, CampaignTemplate $template)
    {
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'template_data' => 'required|array'
        ]);

        try {
            $template->update($request->all());

            return redirect()->route('campaign-templates.show', $template->id)
                ->with('success', 'Template đã được cập nhật');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi cập nhật template: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(CampaignTemplate $template)
    {
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $template->delete();

        return redirect()->route('campaign-templates.index')
            ->with('success', 'Template đã được xóa');
    }

    public function duplicate(Request $request, CampaignTemplate $template)
    {
        $request->validate(['name' => 'required|string|max:255']);

        try {
            $newTemplate = $this->templateService->duplicateTemplate(
                auth()->id(),
                $template,
                $request->name
            );

            return redirect()->route('campaign-templates.show', $newTemplate->id)
                ->with('success', 'Template đã được sao chép');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function createCampaign(Request $request, CampaignTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_date' => 'required|date|after_or_equal:today'
        ]);

        try {
            $overrides = $request->only(['name', 'description', 'start_date']);
            
            $campaign = $this->templateService->createCampaignFromTemplate(
                auth()->id(),
                $template,
                $overrides
            );

            return redirect()->route('campaigns.show', $campaign->id)
                ->with('success', 'Chiến dịch đã được tạo từ template');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function rate(Request $request, CampaignTemplate $template)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500'
        ]);

        try {
            $this->templateService->rateTemplate(
                auth()->id(),
                $template->id,
                $request->rating,
                $request->comment
            );

            return response()->json([
                'success' => true,
                'message' => 'Đánh giá đã được lưu'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function export(CampaignTemplate $template)
    {
        if (!$this->templateService->canAccessTemplate(auth()->id(), $template)) {
            abort(403);
        }

        $exportData = $this->templateService->exportTemplate($template);
        $filename = Str::slug($template->name) . '-template.json';

        return response()->json($exportData)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function import(Request $request)
    {
        $request->validate([
            'template_file' => 'required|file|mimes:json|max:1024'
        ]);

        try {
            $fileContent = file_get_contents($request->file('template_file')->path());
            $templateData = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('File JSON không hợp lệ');
            }

            $template = $this->templateService->importTemplate(auth()->id(), $templateData);

            return redirect()->route('campaign-templates.show', $template->id)
                ->with('success', 'Template đã được import thành công');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi import template: ' . $e->getMessage());
        }
    }
}
```

### Step 5: Frontend Views
```blade
{{-- resources/views/campaign-templates/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Campaign Templates</h2>
                <div>
                    <a href="{{ route('campaign-templates.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tạo Template
                    </a>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-upload"></i> Import
                    </button>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Danh mục</label>
                            <select name="category" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="system" {{ request('category') === 'system' ? 'selected' : '' }}>System</option>
                                <option value="user" {{ request('category') === 'user' ? 'selected' : '' }}>User</option>
                                <option value="shared" {{ request('category') === 'shared' ? 'selected' : '' }}>Shared</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" 
                                   value="{{ request('search') }}" placeholder="Tên hoặc mô tả...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Sắp xếp</label>
                            <select name="sort" class="form-select">
                                <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Mới nhất</option>
                                <option value="usage_count" {{ request('sort') === 'usage_count' ? 'selected' : '' }}>Phổ biến</option>
                                <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Đánh giá</option>
                                <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Tên</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Chỉ của tôi</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="own" value="1" 
                                       {{ request('own') ? 'checked' : '' }}>
                                <label class="form-check-label">Templates của tôi</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i> Lọc
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Templates Grid --}}
            <div class="row">
                @forelse($templates as $template)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ $template->name }}</h6>
                            <span class="badge badge-{{ $template->category === 'system' ? 'primary' : ($template->category === 'shared' ? 'success' : 'secondary') }}">
                                {{ ucfirst($template->category) }}
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="card-text text-muted">{{ Str::limit($template->description, 100) }}</p>
                            
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <small class="text-muted">Đánh giá</small>
                                    <div>
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $template->rating ? 'text-warning' : 'text-muted' }}"></i>
                                        @endfor
                                        <small>({{ $template->ratings_count ?? 0 }})</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Sử dụng</small>
                                    <div><strong>{{ $template->usage_count }}</strong></div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Tác giả</small>
                                    <div>{{ $template->user->name ?? 'System' }}</div>
                                </div>
                            </div>

                            <div class="template-preview">
                                <small class="text-muted">Cấu hình:</small>
                                <ul class="list-unstyled small">
                                    <li><strong>Loại:</strong> {{ ucfirst($template->template_data['campaign_type'] ?? 'N/A') }}</li>
                                    <li><strong>Vốn:</strong> {{ number_format($template->template_data['initial_balance'] ?? 0) }} VND</li>
                                    <li><strong>Chiến lược:</strong> {{ $template->template_data['betting_strategy'] ?? 'N/A' }}</li>
                                    <li><strong>Thời gian:</strong> {{ $template->template_data['days'] ?? 'N/A' }} ngày</li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group w-100">
                                <a href="{{ route('campaign-templates.show', $template->id) }}" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                                <button class="btn btn-primary btn-sm" onclick="createCampaignFromTemplate({{ $template->id }})">
                                    <i class="fas fa-plus"></i> Tạo Campaign
                                </button>
                                @if($template->user_id === auth()->id())
                                <a href="{{ route('campaign-templates.edit', $template->id) }}" 
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <h5>Không có template nào</h5>
                        <p class="text-muted">Hãy tạo template đầu tiên của bạn</p>
                        <a href="{{ route('campaign-templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tạo Template
                        </a>
                    </div>
                </div>
                @endforelse
            </div>

            {{ $templates->appends(request()->query())->links() }}
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('campaign-templates.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Chọn file template (JSON)</label>
                        <input type="file" class="form-control" name="template_file" accept=".json" required>
                        <div class="form-text">Chỉ chấp nhận file JSON được export từ hệ thống</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function createCampaignFromTemplate(templateId) {
    // Redirect to campaign creation with template
    window.location.href = `/campaigns/create?template=${templateId}`;
}
</script>
@endsection
```

## Testing Requirements

### Feature Tests
```php
// tests/Feature/CampaignTemplateTest.php
class CampaignTemplateTest extends TestCase
{
    public function test_user_can_create_template()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->post('/campaign-templates', [
                'name' => 'Test Template',
                'description' => 'Test description',
                'template_data' => [
                    'campaign_type' => 'live',
                    'initial_balance' => 1000000,
                    'betting_strategy' => 'manual'
                ]
            ]);
            
        $response->assertRedirect();
        $this->assertDatabaseHas('campaign_templates', [
            'name' => 'Test Template',
            'user_id' => $user->id
        ]);
    }

    public function test_user_can_create_campaign_from_template()
    {
        $user = User::factory()->create();
        $template = CampaignTemplate::factory()->create([
            'is_public' => true,
            'template_data' => [
                'campaign_type' => 'live',
                'initial_balance' => 1000000,
                'betting_strategy' => 'manual'
            ]
        ]);

        $response = $this->actingAs($user)
            ->post("/campaign-templates/{$template->id}/create-campaign", [
                'name' => 'Test Campaign',
                'start_date' => now()->addDay()->format('Y-m-d')
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'user_id' => $user->id,
            'template_id' => $template->id
        ]);
    }

    public function test_template_usage_count_increments()
    {
        $user = User::factory()->create();
        $template = CampaignTemplate::factory()->create([
            'usage_count' => 5
        ]);

        $service = app(CampaignTemplateService::class);
        $service->createCampaignFromTemplate($user->id, $template, [
            'name' => 'Test Campaign'
        ]);

        $this->assertEquals(6, $template->fresh()->usage_count);
    }
}
```

## Success Criteria
- [ ] Users can browse and filter templates
- [ ] Create custom templates from scratch
- [ ] Create templates from existing campaigns
- [ ] Generate campaigns from templates
- [ ] Rate and review public templates
- [ ] Import/export templates as JSON
- [ ] System templates available for all users
- [ ] Template usage analytics working 
