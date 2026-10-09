<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function getCart(): Cart
    {
        if (!Session::isStarted()) {
            Session::start();
        }

        if (Auth::check()) {
            $user = Auth::user();
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            // Merge anonymous session cart into user's cart
            $guestCartId = Session::get('guest_cart_id');
            $sessionCart = $guestCartId ? Cart::where('id', $guestCartId)->whereNull('user_id')->first() : null;
            if (!$sessionCart) {
                $sessionId = Session::getId();
                $sessionCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->first();
            }

            if ($sessionCart && $sessionCart->id !== $cart->id) {
                foreach ($sessionCart->items as $item) {
                    $existingItem = $cart->items()->where('product_variant_id', $item->product_variant_id)->first();
                    if ($existingItem) {
                        $existingItem->update(['quantity' => $existingItem->quantity + $item->quantity]);
                    } else {
                        $item->update(['cart_id' => $cart->id]);
                    }
                }
                $sessionCart->delete();
                Session::forget('guest_cart_id');
            }

            return $cart->load(['items.variant.product.images']);
        }

        $guestCartId = Session::get('guest_cart_id');
        if ($guestCartId) {
            $cart = Cart::where('id', $guestCartId)->whereNull('user_id')->first();
            if ($cart) {
                return $cart->load(['items.variant.product.images']);
            }
        }

        $sessionId = Session::getId();
        $cart = Cart::firstOrCreate(['session_id' => $sessionId, 'user_id' => null]);
        Session::put('guest_cart_id', $cart->id);
        return $cart->load(['items.variant.product.images']);
    }

    public function addItem(int $variantId, int $quantity = 1): CartItem
    {
        $cart = $this->getCart();
        $variant = ProductVariant::with('product')->whereNull('deleted_at')->find($variantId);

        if (!$variant || !$variant->product || $variant->product->trashed() || !$variant->product->is_active) {
            throw new \DomainException('This product item is currently unavailable.');
        }

        $cartItem = $cart->items()->where('product_variant_id', $variantId)->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            $cartItem = $cart->items()->create([
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
            ]);
        }

        return $cartItem->load(['variant.product.images']);
    }

    public function updateQuantity(int $itemId, int $quantity): ?CartItem
    {
        $cart = $this->getCart();
        $cartItem = $cart->items()->where('id', $itemId)->firstOrFail();

        if ($quantity <= 0) {
            $cartItem->delete();
            return null;
        }

        $cartItem->quantity = $quantity;
        $cartItem->save();

        return $cartItem;
    }

    public function removeItem(int $itemId): bool
    {
        $cart = $this->getCart();
        return (bool) $cart->items()->where('id', $itemId)->delete();
    }

    public function clear(): void
    {
        $cart = $this->getCart();
        $cart->items()->delete();
    }
}
