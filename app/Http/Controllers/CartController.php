<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(Request $request)
    {
        $cart = $this->cartService->getCart();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $this->formatCartResponse($cart),
            ]);
        }

        return redirect()->route('checkout.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1|max:10',
        ]);

        $quantity = $validated['quantity'] ?? 1;

        try {
            $this->cartService->addItem($validated['variant_id'], $quantity);
        } catch (\DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        $cart = $this->cartService->getCart();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item added to bag',
                'cart' => $this->formatCartResponse($cart),
            ]);
        }

        return back()->with('success', 'Item added to bag!');
    }

    public function update(Request $request, int $itemId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0|max:10',
        ]);

        $this->cartService->updateQuantity($itemId, $validated['quantity']);
        $cart = $this->cartService->getCart();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $this->formatCartResponse($cart),
            ]);
        }

        return back();
    }

    public function destroy(Request $request, int $itemId)
    {
        $this->cartService->removeItem($itemId);
        $cart = $this->cartService->getCart();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $this->formatCartResponse($cart),
            ]);
        }

        return back();
    }

    protected function formatCartResponse($cart): array
    {
        $sortedItems = $cart->items->sortBy(function ($item) {
            return [
                $item->variant?->product?->name ?? '',
                \App\Models\ProductVariant::sizeOrderIndex($item->variant?->size),
            ];
        })->values();

        return [
            'items' => $sortedItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->variant->product->name,
                    'size' => $item->variant->size,
                    'price' => (float) $item->variant->effective_price,
                    'quantity' => $item->quantity,
                    'subtotal' => (float) $item->subtotal,
                    'image' => $item->variant->product->primaryImage ? asset($item->variant->product->primaryImage->image_path) : null,
                    'stock' => $item->variant->stock_quantity,
                ];
            }),
            'subtotal' => $cart->subtotal,
            'total_quantity' => $cart->total_quantity,
            'item_count' => $cart->total_quantity,
            'formatted_subtotal' => '$' . number_format($cart->subtotal, 2),
        ];
    }
}
