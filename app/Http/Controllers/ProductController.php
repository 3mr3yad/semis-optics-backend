<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CloudflareR2Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private CloudflareR2Service $r2Service
    ) {}

    private function transformProduct($product): array
    {
        $data = is_array($product) ? $product : $product->toArray();

        if (isset($data['image']) && $data['image'] && !str_starts_with($data['image'], 'http://') && !str_starts_with($data['image'], 'https://')) {
            $data['image'] = $this->r2Service->url($data['image']);
        }

        if (isset($data['category']) && is_array($data['category'])) {
            $data['category']['image'] = $this->r2Service->url($data['category']['image'] ?? null);
        }

        if (isset($data['colors']) && is_array($data['colors'])) {
            $data['colors'] = array_map(function ($color) {
                $image = $color['pivot']['image'] ?? null;

                if ($image && !str_starts_with($image, 'http://') && !str_starts_with($image, 'https://')) {
                    $image = $this->r2Service->url($image);
                }

                $color['image'] = $image;

                return $color;
            }, $data['colors']);
        }

        if (isset($data['models']) && is_array($data['models'])) {
            $data['models'] = array_map(function ($model) {
                if (isset($model['image']) && $model['image'] && !str_starts_with($model['image'], 'http://') && !str_starts_with($model['image'], 'https://')) {
                    $model['image'] = $this->r2Service->url($model['image']);
                }

                return $model;
            }, $data['models']);
        }

        return $data;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $query->with(['category', 'colors', 'models']);

        $query->orderBy('created_at', 'desc');

        $products = $query->paginate($request->input('per_page', 15));

        $data = array_map([$this, 'transformProduct'], $products->items());

        return response()->json([
            'data' => $data,
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'last_page' => $products->lastPage(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with(['category', 'colors', 'models'])->findOrFail($id);

        return response()->json($this->transformProduct($product));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['boolean'],
            'colors' => ['array'],
            'colors.*' => ['exists:colors,id'],
        ]);

        if ($request->hasFile('image')) {
            $upload = $this->r2Service->uploadImage($request->file('image'), 'products');
            $validated['image'] = $upload['url'];
        }

        $product = Product::create($validated);

        if (isset($validated['colors'])) {
            $product->colors()->sync($validated['colors']);
        }

        return response()->json($this->transformProduct($product->load('category', 'colors', 'models')), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['boolean'],
            'colors' => ['array'],
            'colors.*' => ['exists:colors,id'],
        ]);

        if ($request->hasFile('image')) {
            $upload = $this->r2Service->uploadImage($request->file('image'), 'products');
            $validated['image'] = $upload['url'];
        }

        $product->update($validated);

        if (isset($validated['colors'])) {
            $product->colors()->sync($validated['colors']);
        }

        return response()->json($this->transformProduct($product->load('category', 'colors', 'models')));
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $product->delete();

        return response()->json(null, 204);
    }
}
