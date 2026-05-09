<?php

namespace App\Http\Controllers;

use App\Models\FrameColor;
use App\Models\Magnification;
use App\Models\Product;
use App\Models\TrustBadge;
use App\Services\CloudflareR2Service;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        $validated = $this->validateProductPayload($request);
        $productAttributes = $this->normalizeProductAttributes($validated);
        $product = Product::create($productAttributes);

        $this->syncProductRelationsFromValidated($product, $validated);

        return response()->json($this->transformProduct($product), 201);
    }

    public function dashboardUpdate(Request $request, Product $product)
    {
        $validated = $this->validateProductPayload($request, $product->id);
        $productAttributes = $this->normalizeProductAttributes($validated);
        $product->update($productAttributes);

        $this->syncProductRelationsFromValidated($product, $validated);

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

    private function validateProductPayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
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
    }

    private function normalizeProductAttributes(array $validated): array
    {
        return [
            'slug' => $validated['slug'],
            'name' => $validated['name'],
            'badge' => $validated['badge'] ?? null,
            'price' => $validated['price'],
            'currency' => strtoupper($validated['currency']),
            'rating_score' => data_get($validated, 'rating.score', 0),
            'total_reviews' => data_get($validated, 'rating.total_reviews', 0),
            'main_image' => data_get($validated, 'media.main_image'),
            'is_active' => $validated['is_active'] ?? true,
        ];
    }

    private function syncProductRelationsFromValidated(Product $product, array $validated): void
    {
        $gallery = data_get($validated, 'media.gallery', []);
        $product->media()->delete();
        foreach ($gallery as $index => $item) {
            $product->media()->create([
                'type' => $item['type'],
                'url' => $item['url'],
                'thumbnail' => $item['thumbnail'] ?? null,
                'sort_order' => $index,
            ]);
        }

        $product->features()->delete();
        foreach (($validated['features'] ?? []) as $index => $feature) {
            $product->features()->create([
                'icon' => $feature['icon'] ?? null,
                'title' => $feature['title'],
                'description' => $feature['description'],
                'sort_order' => $index,
            ]);
        }

        $product->technicalSpecifications()->delete();
        foreach (($validated['technical_specifications'] ?? []) as $index => $spec) {
            $product->technicalSpecifications()->create([
                'parameter' => $spec['parameter'],
                'specification' => $spec['specification'],
                'sort_order' => $index,
            ]);
        }

        $magnificationItems = data_get($validated, 'variants.magnification', []);
        $magnificationSync = [];
        foreach ($magnificationItems as $item) {
            $magnification = Magnification::firstOrCreate(
                ['code' => $item['id']],
                ['label' => $item['label'], 'is_active' => true]
            );
            $magnificationSync[$magnification->id] = ['available' => (bool) $item['available']];
        }
        $product->magnifications()->sync($magnificationSync);

        $colorItems = data_get($validated, 'variants.frame_colors', []);
        $colorSync = [];
        foreach ($colorItems as $item) {
            $color = FrameColor::firstOrCreate(
                ['code' => $item['id']],
                [
                    'name' => $item['name'],
                    'hex' => $item['hex'] ?? null,
                    'is_active' => true,
                ]
            );
            $colorSync[$color->id] = ['available' => (bool) $item['available']];
        }
        $product->frameColors()->sync($colorSync);

        $badgeNames = array_values(array_filter(Arr::wrap($validated['trust_badges'] ?? []), fn ($v) => is_string($v) && trim($v) !== ''));
        $badgeIds = [];
        foreach ($badgeNames as $name) {
            $badgeIds[] = TrustBadge::firstOrCreate(['name' => $name])->id;
        }
        $product->trustBadges()->sync($badgeIds);
    }

    private function transformProduct(Product $product): array
    {
        $product->loadMissing([
            'media',
            'magnifications',
            'frameColors',
            'features',
            'technicalSpecifications',
            'trustBadges',
        ]);

        $gallery = $product->media
            ->map(function ($media): array {
                return [
                    'type' => $media->type,
                    'url' => $this->resolveMediaUrl($media->url),
                    'thumbnail' => $this->resolveMediaUrl($media->thumbnail),
                ];
            })
            ->values()
            ->all();

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
                'main_image' => $this->resolveMediaUrl($product->main_image),
                'gallery' => $gallery,
            ],
            'variants' => [
                'magnification' => $product->magnifications
                    ->map(fn ($m): array => [
                        'id' => $m->code,
                        'label' => $m->label,
                        'available' => (bool) $m->pivot?->available,
                    ])
                    ->values()
                    ->all(),
                'frame_colors' => $product->frameColors
                    ->map(fn ($c): array => [
                        'id' => $c->code,
                        'name' => $c->name,
                        'hex' => $c->hex,
                        'available' => (bool) $c->pivot?->available,
                    ])
                    ->values()
                    ->all(),
            ],
            'features' => $product->features
                ->map(fn ($f): array => [
                    'icon' => $f->icon,
                    'title' => $f->title,
                    'description' => $f->description,
                ])
                ->values()
                ->all(),
            'technical_specifications' => $product->technicalSpecifications
                ->map(fn ($s): array => [
                    'parameter' => $s->parameter,
                    'specification' => $s->specification,
                ])
                ->values()
                ->all(),
            'trust_badges' => $product->trustBadges
                ->pluck('name')
                ->values()
                ->all(),
            'is_active' => $product->is_active,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }

    private function resolveMediaUrl(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return $value;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $path = ltrim($value, '/');
        $configuredUrl = rtrim((string) config('filesystems.disks.r2.url'), '/');

        if ($configuredUrl !== '') {
            return $configuredUrl.'/'.$path;
        }

        $endpoint = rtrim((string) config('filesystems.disks.r2.endpoint'), '/');
        $bucket = (string) config('filesystems.disks.r2.bucket');

        return $endpoint.'/'.$bucket.'/'.$path;
    }
}
