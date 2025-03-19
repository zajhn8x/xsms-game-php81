
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\LotteryBetService;
use Illuminate\Support\Facades\Auth;

class BetController extends Controller
{
    protected $betService;

    public function __construct(LotteryBetService $betService)
    {
        $this->betService = $betService;
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lo_number' => 'required|integer|min:0|max:99',
            'amount' => 'required|integer|min:1000'
        ]);

        try {
            $bet = $this->betService->placeBet(Auth::id(), $validated);
            
            return redirect()->back()
                ->with('success', 'Đặt cược thành công cho số ' . $bet->lo_number);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
