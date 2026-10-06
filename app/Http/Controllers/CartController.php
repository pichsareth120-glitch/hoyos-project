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
                'cart' => [
                    'items' => $cart->items->map(function ($item) {
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
                ],
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
        $this->cartService->addItem($validated['variant_id'], $quantity);
        $cart = $this->cartService->getCart();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item added to bag',
                'cart' => [
                    'items' => $cart->items->map(function ($item) {
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
                ],
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
                'cart' => [
                    'items' => $cart->items->map(function ($item) {
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
                ],
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
                'cart' => [
                    'items' => $cart->items->map(function ($item) {
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
                ],
            ]);
        }

        return back();
    }
}
