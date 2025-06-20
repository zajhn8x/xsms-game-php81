<?php

namespace App\Http\Controllers;

use App\Models\CampaignTemplate;
use App\Services\CampaignTemplateService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Micro-task 2.1.2.5: Template management UI (6h)
 * Controller for managing campaign templates
 */
class CampaignTemplateController extends Controller
{
    protected CampaignTemplateService $templateService;

    public function __construct(CampaignTemplateService $templateService)
    {
        $this->templateService = $templateService;
        $this->middleware('auth');
    }

    /**
     * Display listing of templates
     */
    public function index(Request $request)
    {
        $filters = $request->only(['category', 'search', 'sort', 'order', 'own']);
        $templates = $this->templateService->getTemplates(auth()->id(), $filters);

        return view('campaign-templates.index', compact('templates', 'filters'));
    }

    /**
     * Show form for creating new template
     */
    public function create()
    {
        return view('campaign-templates.create');
    }

    /**
     * Store newly created template
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'template_data' => 'required|array',
            'template_data.campaign_type' => 'required|in:live,historical',
            'template_data.initial_balance' => 'required|numeric|min:100000',
            'template_data.betting_strategy' => 'required|string'
        ]);

        try {
            $template = $this->templateService->createTemplate(
                auth()->id(),
                $request->all()
            );

            return redirect()
                ->route('campaign-templates.show', $template->id)
                ->with('success', 'Template đã được tạo thành công');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Lỗi tạo template: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display specified template
     */
    public function show(CampaignTemplate $template)
    {
        if (!$this->templateService->canAccessTemplate(auth()->id(), $template)) {
            abort(403, 'Không có quyền truy cập template này');
        }

        $template->load(['user:id,name', 'ratings.user:id,name']);
        $userRating = $template->ratings()->where('user_id', auth()->id())->first();
        $previewData = $template->getPreviewData();

        return view('campaign-templates.show', compact('template', 'userRating', 'previewData'));
    }

    /**
     * Show form for editing template
     */
    public function edit(CampaignTemplate $template)
    {
        if ($template->user_id !== auth()->id()) {
            abort(403, 'Chỉ có thể chỉnh sửa template của bạn');
        }

        return view('campaign-templates.edit', compact('template'));
    }

    /**
     * Update specified template
     */
    public function update(Request $request, CampaignTemplate $template)
    {
        if ($template->user_id !== auth()->id()) {
            abort(403, 'Chỉ có thể chỉnh sửa template của bạn');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'template_data' => 'required|array'
        ]);

        try {
            $template->update($request->all());

            return redirect()
                ->route('campaign-templates.show', $template->id)
                ->with('success', 'Template đã được cập nhật');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Lỗi cập nhật template: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove specified template
     */
    public function destroy(CampaignTemplate $template)
    {
        if ($template->user_id !== auth()->id()) {
            abort(403, 'Chỉ có thể xóa template của bạn');
        }

        $template->delete();

        return redirect()
            ->route('campaign-templates.index')
            ->with('success', 'Template đã được xóa');
    }

    /**
     * Duplicate template
     */
    public function duplicate(Request $request, CampaignTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        try {
            $newTemplate = $this->templateService->duplicateTemplate(
                auth()->id(),
                $template,
                $request->name
            );

            return redirect()
                ->route('campaign-templates.show', $newTemplate->id)
                ->with('success', 'Template đã được sao chép');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Create campaign from template
     */
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

            return redirect()
                ->route('campaigns.show', $campaign->id)
                ->with('success', 'Chiến dịch đã được tạo từ template');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Rate template (AJAX)
     */
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

    /**
     * Export template as JSON
     */
    public function export(CampaignTemplate $template)
    {
        if (!$this->templateService->canAccessTemplate(auth()->id(), $template)) {
            abort(403, 'Không có quyền truy cập template này');
        }

        $exportData = $this->templateService->exportTemplate($template);
        $filename = 'template_' . $template->id . '_' . now()->format('Ymd_His') . '.json';

        return response()->json($exportData)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Import template from JSON
     */
    public function import(Request $request)
    {
        $request->validate([
            'template_file' => 'required|file|mimes:json|max:2048'
        ]);

        try {
            $content = file_get_contents($request->file('template_file')->getPathname());
            $templateData = json_decode($content, true);

            if (!$templateData) {
                throw new \Exception('File JSON không hợp lệ');
            }

            $template = $this->templateService->importTemplate(auth()->id(), $templateData);

            return redirect()
                ->route('campaign-templates.show', $template->id)
                ->with('success', 'Template đã được import thành công');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi import template: ' . $e->getMessage());
        }
    }

    /**
     * Get popular templates (AJAX)
     */
    public function popular()
    {
        $templates = CampaignTemplate::popular(10)
            ->where('is_public', true)
            ->with(['user:id,name'])
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'usage_count' => $template->usage_count,
                    'rating' => $template->rating,
                    'author' => $template->user->name ?? 'System',
                    'preview' => $template->getPreviewData()
                ];
            });

        return response()->json($templates);
    }

    /**
     * Search templates (AJAX)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $templates = CampaignTemplate::where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%');
            })
            ->where(function ($q) {
                $q->where('is_public', true)
                  ->orWhere('user_id', auth()->id())
                  ->orWhere('category', 'system');
            })
            ->with(['user:id,name'])
            ->limit(20)
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'category' => $template->category,
                    'author' => $template->user->name ?? 'System',
                    'preview' => $template->getPreviewData()
                ];
            });

        return response()->json($templates);
    }
}
