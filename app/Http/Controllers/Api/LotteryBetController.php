<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LotteryBetController extends Controller
{
    public function index()
    {
        $bets = LotteryBet::with(['user', 'result'])->paginate(10);
        return response()->json($bets);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'lottery_result_id' => 'required|exists:lottery_results,id',
            'bet_numbers' => 'required|array',
            'bet_amount' => 'required|numeric|min:0',
            'status' => 'required|string'
        ]);

        $bet = LotteryBet::create($validated);
        return response()->json($bet, 201);
    }

    public function show(string $id)
    {
        $bet = LotteryBet::with(['user', 'result'])->findOrFail($id);
        return response()->json($bet);
    }

    public function update(Request $request, string $id)
    {
        $bet = LotteryBet::findOrFail($id);
        
        $validated = $request->validate([
            'user_id' => 'exists:users,id',
            'lottery_result_id' => 'exists:lottery_results,id',
            'bet_numbers' => 'array',
            'bet_amount' => 'numeric|min:0',
            'status' => 'string'
        ]);

        $bet->update($validated);
        return response()->json($bet);
    }

    public function destroy(string $id)
    {
        $bet = LotteryBet::findOrFail($id);
        $bet->delete();
        return response()->json(null, 204);
    }
}
