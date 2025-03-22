<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LotteryFormulaController extends Controller
{
    public function index()
    {
        $cauLos = LotteryFormula::with('cauMeta')->paginate(10);
        return response()->json($cauLos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lottery_cau_meta_id' => 'required|exists:lottery_cau_meta,id',
            'number' => 'required|string|max:2',
            'status' => 'required|boolean',
            'day_missed' => 'required|integer'
        ]);

        $cauLo = LotteryFormula::create($validated);
        return response()->json($cauLo, 201);
    }

    public function show(string $id)
    {
        $cauLo = LotteryFormula::with('cauMeta')->findOrFail($id);
        return response()->json($cauLo);
    }

    public function update(Request $request, string $id)
    {
        $cauLo = LotteryFormula::findOrFail($id);

        $validated = $request->validate([
            'lottery_cau_meta_id' => 'exists:lottery_cau_meta,id',
            'number' => 'string|max:2',
            'status' => 'boolean',
            'day_missed' => 'integer'
        ]);

        $cauLo->update($validated);
        return response()->json($cauLo);
    }

    public function destroy(string $id)
    {
        $cauLo = LotteryFormula::findOrFail($id);
        $cauLo->delete();
        return response()->json(null, 204);
    }
}
