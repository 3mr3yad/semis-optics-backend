<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ColorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Color::query();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $query->orderBy('name');

        $colors = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $colors->items(),
            'current_page' => $colors->currentPage(),
            'per_page' => $colors->perPage(),
            'total' => $colors->total(),
            'last_page' => $colors->lastPage(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $color = Color::with('products')->findOrFail($id);

        return response()->json($color);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'hex_code' => ['required', 'string', 'max:7', 'unique:colors,hex_code', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
        ]);

        $color = Color::create($validated);

        return response()->json($color, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $color = Color::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'hex_code' => ['sometimes', 'string', 'max:7', 'unique:colors,hex_code,'.$id, 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
        ]);

        $color->update($validated);

        return response()->json($color);
    }

    public function destroy(string $id): JsonResponse
    {
        $color = Color::findOrFail($id);

        if ($color->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete color with products.',
            ], 422);
        }

        $color->delete();

        return response()->json(null, 204);
    }
}
