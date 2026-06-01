<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CloudflareR2Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(
        private CloudflareR2Service $r2Service
    ) {}

    private function transformCategory($category): array
    {
        $data = is_array($category) ? $category : $category->toArray();

        // Only transform image URL if it's not already a full URL
        if (isset($data['image']) && $data['image'] && !str_starts_with($data['image'], 'http://') && !str_starts_with($data['image'], 'https://')) {
            $data['image'] = $this->r2Service->url($data['image']);
        }

        if (isset($data['children']) && is_array($data['children'])) {
            $data['children'] = array_map([$this, 'transformCategory'], $data['children']);
        }

        if (isset($data['parent']) && is_array($data['parent'])) {
            $data['parent'] = $this->transformCategory($data['parent']);
        }

        return $data;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by parent_id (null for root categories)
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Filter by slug
        if ($request->has('slug')) {
            $query->where('slug', $request->slug);
        }

        // Include children
        if ($request->boolean('include_children')) {
            $query->with('children');
        }

        // Include parent
        if ($request->boolean('include_parent')) {
            $query->with('parent');
        }

        // Order by sort_order then name
        $query->orderBy('sort_order')->orderBy('name');

        $categories = $query->paginate($request->input('per_page', 15));

        $data = array_map([$this, 'transformCategory'], $categories->items());

        return response()->json([
            'data' => $data,
            'current_page' => $categories->currentPage(),
            'per_page' => $categories->perPage(),
            'total' => $categories->total(),
            'last_page' => $categories->lastPage(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $category = Category::with(['parent', 'children'])->findOrFail($id);

        return response()->json($this->transformCategory($category));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'sort_order' => ['integer'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        if ($request->hasFile('image')) {
            $upload = $this->r2Service->uploadImage($request->file('image'), 'categories');
            $validated['image'] = $upload['url'];
        }

        $category = Category::create($validated);

        return response()->json($this->transformCategory($category), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug,'.$id],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'sort_order' => ['integer'],
        ]);

        if (isset($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        if ($request->hasFile('image')) {
            $upload = $this->r2Service->uploadImage($request->file('image'), 'categories');
            $validated['image'] = $upload['url'];
        }

        $category->update($validated);

        return response()->json($this->transformCategory($category));
    }

    public function destroy(string $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        // Prevent deletion if category has children
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories.',
            ], 422);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
