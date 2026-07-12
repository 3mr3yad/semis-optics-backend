<?php

namespace App\Http\Controllers;

use App\Models\ProductModel;
use App\Services\CloudflareR2Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductModelController extends Controller
{
    public function __construct(
        private CloudflareR2Service $r2Service
    ) {}

    private function transformProductModel($model): array
    {
        $data = is_array($model) ? $model : $model->toArray();

        if (isset($data['image']) && $data['image'] && !str_starts_with($data['image'], 'http://') && !str_starts_with($data['image'], 'https://')) {
            $data['image'] = $this->r2Service->url($data['image']);
        }

        return $data;
    }

    public function index(Request $request): JsonResponse
    {
        $query = ProductModel::query();

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $query->with('product');

        $query->orderBy('created_at', 'desc');

        $models = $query->paginate($request->input('per_page', 15));

        $data = array_map([$this, 'transformProductModel'], $models->items());

        return response()->json([
            'data' => $data,
            'current_page' => $models->currentPage(),
            'per_page' => $models->perPage(),
            'total' => $models->total(),
            'last_page' => $models->lastPage(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $model = ProductModel::with('product')->findOrFail($id);

        return response()->json($this->transformProductModel($model));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_after_discount' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'is_active' => ['boolean'],
            'attributes' => ['array'],
        ]);

        if ($request->hasFile('image')) {
            $upload = $this->r2Service->uploadImage($request->file('image'), 'product-models');
            $validated['image'] = $upload['url'];
        }

        $model = ProductModel::create($validated);

        return response()->json($this->transformProductModel($model->load('product')), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $model = ProductModel::findOrFail($id);

        $validated = $request->validate([
            'product_id' => ['sometimes', 'exists:products,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_after_discount' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'is_active' => ['boolean'],
            'attributes' => ['array'],
        ]);

        if ($request->hasFile('image')) {
            $upload = $this->r2Service->uploadImage($request->file('image'), 'product-models');
            $validated['image'] = $upload['url'];
        }

        $model->update($validated);

        return response()->json($this->transformProductModel($model->load('product')));
    }

    public function destroy(string $id): JsonResponse
    {
        $model = ProductModel::findOrFail($id);

        $model->delete();

        return response()->json(null, 204);
    }
}
