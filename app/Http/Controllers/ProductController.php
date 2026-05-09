<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CloudflareR2Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map(fn (Product $product) => $this->transformProduct($product));

        return response()->json([
            'data' => $products,
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json($this->transformProduct($product));
    }

    public function dashboardIndex()
    {
        $products = Product::query()
            ->latest()
            ->get()
            ->map(fn (Product $product) => $this->transformProduct($product));

        return response()->json([
            'data' => $products,
        ]);
    }

    public function dashboardStore(Request $request)
    {
        $payload = $this->validateAndNormalizeProduct($request);
        $product = Product::create($payload);

        return response()->json($this->transformProduct($product), 201);
    }

    public function dashboardUpdate(Request $request, Product $product)
    {
        $payload = $this->validateAndNormalizeProduct($request, $product->id);
        $product->update($payload);

        return response()->json($this->transformProduct($product->fresh()));
    }

    public function dashboardDestroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function uploadImage(Request $request, CloudflareR2Service $r2Service)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:4096'],
            'section' => ['nullable', 'string', Rule::in(['product', 'testimonial', 'category', 'brand', 'general'])],
        ]);

        $directory = $r2Service->directoryFromSection($validated['section'] ?? 'product');

        $uploaded = $r2Service->uploadImage(
            $validated['image'],
            $directory
        );

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'section' => $validated['section'] ?? 'product',
            'folder' => $directory,
            'data' => $uploaded,
        ], 201);
    }

    private function validateAndNormalizeProduct(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'rating' => ['required', 'array'],
            'rating.score' => ['required', 'numeric', 'between:0,5'],
            'rating.total_reviews' => ['required', 'integer', 'min:0'],
            'media' => ['required', 'array'],
            'media.main_image' => ['nullable', 'url', 'max:2048'],
            'media.gallery' => ['nullable', 'array'],
            'media.gallery.*.type' => ['required_with:media.gallery', 'string', 'max:20'],
            'media.gallery.*.url' => ['required_with:media.gallery', 'url', 'max:2048'],
            'media.gallery.*.thumbnail' => ['nullable', 'url', 'max:2048'],
            'variants' => ['required', 'array'],
            'variants.magnification' => ['nullable', 'array'],
            'variants.magnification.*.id' => ['required_with:variants.magnification', 'string', 'max:50'],
            'variants.magnification.*.label' => ['required_with:variants.magnification', 'string', 'max:100'],
            'variants.magnification.*.available' => ['required_with:variants.magnification', 'boolean'],
            'variants.frame_colors' => ['nullable', 'array'],
            'variants.frame_colors.*.id' => ['required_with:variants.frame_colors', 'string', 'max:50'],
            'variants.frame_colors.*.name' => ['required_with:variants.frame_colors', 'string', 'max:100'],
            'variants.frame_colors.*.hex' => ['nullable', 'string', 'max:10'],
            'variants.frame_colors.*.available' => ['required_with:variants.frame_colors', 'boolean'],
            'features' => ['nullable', 'array'],
            'features.*.icon' => ['nullable', 'string', 'max:100'],
            'features.*.title' => ['required_with:features', 'string', 'max:100'],
            'features.*.description' => ['required_with:features', 'string', 'max:2000'],
            'technical_specifications' => ['nullable', 'array'],
            'technical_specifications.*.parameter' => ['required_with:technical_specifications', 'string', 'max:150'],
            'technical_specifications.*.specification' => ['required_with:technical_specifications', 'string', 'max:500'],
            'trust_badges' => ['nullable', 'array'],
            'trust_badges.*' => ['string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return [
            'slug' => $validated['slug'],
            'name' => $validated['name'],
            'badge' => $validated['badge'] ?? null,
            'price' => $validated['price'],
            'currency' => strtoupper($validated['currency']),
            'rating_score' => data_get($validated, 'rating.score', 0),
            'total_reviews' => data_get($validated, 'rating.total_reviews', 0),
            'main_image' => data_get($validated, 'media.main_image'),
            'gallery' => data_get($validated, 'media.gallery', []),
            'magnification' => data_get($validated, 'variants.magnification', []),
            'frame_colors' => data_get($validated, 'variants.frame_colors', []),
            'features' => $validated['features'] ?? [],
            'technical_specifications' => $validated['technical_specifications'] ?? [],
            'trust_badges' => $validated['trust_badges'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
        ];
    }

    private function transformProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'badge' => $product->badge,
            'price' => (float) $product->price,
            'currency' => $product->currency,
            'rating' => [
                'score' => (float) $product->rating_score,
                'total_reviews' => $product->total_reviews,
            ],
            'media' => [
                'main_image' => $product->main_image,
                'gallery' => $product->gallery ?? [],
            ],
            'variants' => [
                'magnification' => $product->magnification ?? [],
                'frame_colors' => $product->frame_colors ?? [],
            ],
            'features' => $product->features ?? [],
            'technical_specifications' => $product->technical_specifications ?? [],
            'trust_badges' => $product->trust_badges ?? [],
            'is_active' => $product->is_active,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }
}
