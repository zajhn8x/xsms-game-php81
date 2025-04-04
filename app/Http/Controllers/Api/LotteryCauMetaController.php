<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LotteryCauMetaController extends Controller
{
    public function index()
    {
        $metas = LotteryCauMeta::with('cauLos')->paginate(10);
        return response()->json($metas);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $meta = LotteryCauMeta::create($validated);
        return response()->json($meta, 201);
    }

    public function show(string $id)
    {
        $meta = LotteryCauMeta::with('cauLos')->findOrFail($id);
        return response()->json($meta);
    }

    public function update(Request $request, string $id)
    {
        $meta = LotteryCauMeta::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string'
        ]);

        $meta->update($validated);
        return response()->json($meta);
    }

    public function destroy(string $id)
    {
        $meta = LotteryCauMeta::findOrFail($id);
        $meta->delete();
        return response()->json(null, 204);
    }
}
