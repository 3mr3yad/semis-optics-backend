<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    private function transformOrder($order): array
    {
        $data = is_array($order) ? $order : $order->toArray();

        if (isset($data['product']) && is_array($data['product'])) {
            $data['product']['image'] = app(\App\Services\CloudflareR2Service::class)->url($data['product']['image'] ?? null);
        }

        return $data;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Order::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('disposition_id')) {
            $query->where('disposition_id', $request->disposition_id);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%')
                  ->orWhere('phone', 'like', '%'.$request->search.'%');
        }

        $query->with(['product', 'color', 'disposition']);

        $query->orderBy('created_at', 'desc');

        $orders = $query->paginate($request->input('per_page', 15));

        $data = array_map([$this, 'transformOrder'], $orders->items());

        return response()->json([
            'data' => $data,
            'current_page' => $orders->currentPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
            'last_page' => $orders->lastPage(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $order = Order::with(['product', 'color', 'disposition'])->findOrFail($id);

        return response()->json($this->transformOrder($order));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'position' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'product_id' => ['required', 'exists:products,id'],
            'color_id' => ['nullable', 'exists:colors,id'],
            'disposition_id' => ['nullable', 'exists:dispositions,id'],
            'status' => ['in:pending,processing,completed,cancelled'],
            'note' => ['nullable', 'string'],
        ]);

        $validated['status'] = $validated['status'] ?? 'pending';

        $order = Order::create($validated);

        return response()->json($this->transformOrder($order->load('product', 'color', 'disposition')), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'position' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'product_id' => ['sometimes', 'exists:products,id'],
            'color_id' => ['nullable', 'exists:colors,id'],
            'disposition_id' => ['nullable', 'exists:dispositions,id'],
            'status' => ['in:pending,processing,completed,cancelled'],
            'note' => ['nullable', 'string'],
        ]);

        $order->update($validated);

        return response()->json($this->transformOrder($order->load('product', 'color', 'disposition')));
    }

    public function destroy(string $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $order->delete();

        return response()->json(null, 204);
    }
}
