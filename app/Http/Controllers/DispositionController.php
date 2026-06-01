<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DispositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Disposition::query();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $query->orderBy('sort_order')->orderBy('name');

        $dispositions = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $dispositions->items(),
            'current_page' => $dispositions->currentPage(),
            'per_page' => $dispositions->perPage(),
            'total' => $dispositions->total(),
            'last_page' => $dispositions->lastPage(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $disposition = Disposition::with('orders')->findOrFail($id);

        return response()->json($disposition);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $disposition = Disposition::create($validated);

        return response()->json($disposition, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $disposition = Disposition::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $disposition->update($validated);

        return response()->json($disposition);
    }

    public function destroy(string $id): JsonResponse
    {
        $disposition = Disposition::findOrFail($id);

        if ($disposition->orders()->exists()) {
            return response()->json([
                'message' => 'Cannot delete disposition with orders.',
            ], 422);
        }

        $disposition->delete();

        return response()->json(null, 204);
    }
}
