<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LotteryLogController extends Controller
{
    public function index()
    {
        $logs = LotteryLog::with('user')->paginate(10);
        return response()->json($logs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'action' => 'required|string',
            'data' => 'required|array'
        ]);

        $log = LotteryLog::create($validated);
        return response()->json($log, 201);
    }

    public function show(string $id)
    {
        $log = LotteryLog::with('user')->findOrFail($id);
        return response()->json($log);
    }

    public function update(Request $request, string $id)
    {
        $log = LotteryLog::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'exists:users,id',
            'action' => 'string',
            'data' => 'array'
        ]);

        $log->update($validated);
        return response()->json($log);
    }

    public function destroy(string $id)
    {
        $log = LotteryLog::findOrFail($id);
        $log->delete();
        return response()->json(null, 204);
    }
}
