<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LotteryResultController extends Controller
{
    public function index()
    {
        $results = LotteryResult::orderBy('draw_date', 'desc')->paginate(10);
        return response()->json($results);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'draw_date' => 'required|date',
            'prizes' => 'required|array',
            'lo_array' => 'required|array'
        ]);

        $result = LotteryResult::create($validated);
        return response()->json($result, 201);
    }

    public function show(string $id)
    {
        $result = LotteryResult::findOrFail($id);
        return response()->json($result);
    }

    public function update(Request $request, string $id)
    {
        $result = LotteryResult::findOrFail($id);
        
        $validated = $request->validate([
            'draw_date' => 'date',
            'prizes' => 'array',
            'lo_array' => 'array'
        ]);

        $result->update($validated);
        return response()->json($result);
    }

    public function destroy(string $id)
    {
        $result = LotteryResult::findOrFail($id);
        $result->delete();
        return response()->json(null, 204);
    }
}
