<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignTemplate;
use App\Models\TemplateRating;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Micro-task 2.1.2.2: Template creation API (4h)
 * Micro-task 2.1.2.3: Template application logic (5h)
 * Campaign Template Service for managing template operations
 */
class CampaignTemplateService
{
    /**
     * Get templates with filtering and pagination
     */
    public function getTemplates($userId, array $filters = [], $perPage = 15)
    {
        $query = CampaignTemplate::with(['user:id,name', 'ratings']);

        // Filter by access permissions
        $query->where(function ($q) use ($userId) {
            $q->where('is_public', true)
              ->orWhere('user_id', $userId)
              ->orWhere('category', 'system');
        });

        // Apply filters
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['own']) && $filters['own'] == '1') {
            $query->where('user_id', $userId);
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        $allowedSorts = ['created_at', 'name', 'usage_count', 'rating'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create new template
     */
    public function createTemplate($userId, array $data): CampaignTemplate
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

    /**
     * Create template from existing campaign
     */
    public function createFromCampaign($userId, Campaign $campaign, array $templateInfo): CampaignTemplate
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
            'days' => $campaign->days,
            'target_profit' => $campaign->target_profit,
            'notes' => $campaign->notes
        ];

        return $this->createTemplate($userId, [
            'name' => $templateInfo['name'],
            'description' => $templateInfo['description'] ?? null,
            'is_public' => $templateInfo['is_public'] ?? false,
            'template_data' => $templateData
        ]);
    }

    /**
     * Create campaign from template
     */
    public function createCampaignFromTemplate($userId, CampaignTemplate $template, array $overrides = []): Campaign
    {
        if (!$template->canAccess($userId)) {
            throw new \Exception('Không có quyền truy cập template này');
        }

        return $template->createCampaign($userId, $overrides);
    }

    /**
     * Duplicate existing template
     */
    public function duplicateTemplate($userId, CampaignTemplate $template, $newName): CampaignTemplate
    {
        if (!$template->canAccess($userId)) {
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

    /**
     * Rate a template
     */
    public function rateTemplate($userId, $templateId, $rating, $comment = null): TemplateRating
    {
        $template = CampaignTemplate::findOrFail($templateId);

        if (!$template->is_public && $template->user_id !== $userId) {
            throw new \Exception('Không thể đánh giá template này');
        }

        if ($rating < 1 || $rating > 5) {
            throw new \Exception('Rating phải từ 1 đến 5');
        }

        return TemplateRating::updateOrCreate(
            ['template_id' => $templateId, 'user_id' => $userId],
            ['rating' => $rating, 'comment' => $comment]
        );
    }

    /**
     * Export template as JSON
     */
    public function exportTemplate(CampaignTemplate $template): array
    {
        return [
            'name' => $template->name,
            'description' => $template->description,
            'template_data' => $template->template_data,
            'exported_at' => now()->toISOString(),
            'version' => '1.0'
        ];
    }

    /**
     * Import template from JSON
     */
    public function importTemplate($userId, array $templateData): CampaignTemplate
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

    /**
     * Get system templates
     */
    public function getSystemTemplates(): array
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
                'description' => 'Chiến lược cân bằng giữa bảo toàn và tăng trưởng',
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

    /**
     * Check if user can access template
     */
    public function canAccessTemplate($userId, CampaignTemplate $template): bool
    {
        return $template->canAccess($userId);
    }

    /**
     * Validate template data structure
     */
    protected function validateTemplateData(array $data): array
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
            'days' => 'nullable|integer|min:1|max:365',
            'target_profit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
