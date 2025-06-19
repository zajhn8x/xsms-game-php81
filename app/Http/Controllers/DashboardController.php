<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->middleware('auth');
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $dashboardData = $this->dashboardService->getUserDashboard(Auth::id());

        return view('dashboard.index', compact('dashboardData'));
    }

    public function admin()
    {
        // Check if user has admin permissions
        if (Auth::user()->email !== 'admin@example.com') {
            abort(403, 'Unauthorized');
        }

        $dashboardData = $this->dashboardService->getAdminDashboard();

        return view('admin.dashboard', compact('dashboardData'));
    }

    // API endpoints for AJAX requests
    public function apiUserDashboard()
    {
        $dashboardData = $this->dashboardService->getUserDashboard(Auth::id());

        return response()->json($dashboardData);
    }

    public function apiAdminDashboard()
    {
        if (Auth::user()->email !== 'admin@example.com') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $dashboardData = $this->dashboardService->getAdminDashboard();

        return response()->json($dashboardData);
    }

    public function apiChartData(Request $request)
    {
        $chartType = $request->get('type');
        $dashboardData = $this->dashboardService->getUserDashboard(Auth::id());

        switch ($chartType) {
            case 'profit_loss':
                return response()->json($dashboardData['charts_data']['profit_loss_chart']);
            case 'win_rate':
                return response()->json($dashboardData['charts_data']['win_rate_chart']);
            case 'campaign_performance':
                return response()->json($dashboardData['charts_data']['campaign_performance']);
            default:
                return response()->json(['error' => 'Invalid chart type'], 400);
        }
    }

    public function apiAdminChartData(Request $request)
    {
        if (Auth::user()->email !== 'admin@example.com') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $chartType = $request->get('type');
        $dashboardData = $this->dashboardService->getAdminDashboard();

        switch ($chartType) {
            case 'daily_revenue':
                return response()->json($dashboardData['system_charts']['daily_revenue']);
            case 'user_activity':
                return response()->json($dashboardData['system_charts']['user_activity']);
            case 'campaign_trends':
                return response()->json($dashboardData['system_charts']['campaign_trends']);
            default:
                return response()->json(['error' => 'Invalid chart type'], 400);
        }
    }
}
