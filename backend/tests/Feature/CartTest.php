<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

function getTestVariant(int $minStock = 10): ProductVariant
{
    return ProductVariant::where('stock_quantity', '>=', $minStock)->first() 
        ?: ProductVariant::factory()->create(['stock_quantity' => $minStock + 10]);
}

test('guest can add product variant to cart', function () {
    $variant = getTestVariant(5);

    $response = $this->withSession(['initialized' => true])->postJson('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure(['success', 'cart' => ['items', 'item_count', 'subtotal']]);

    expect($response->json('cart.item_count'))->toBeGreaterThanOrEqual(1);
});

test('user can update cart item quantity', function () {
    $user = User::factory()->create();
    $variant = getTestVariant(10);

    // Add initial item as user
    $addResponse = $this->actingAs($user)->postJson('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $cartItems = $addResponse->json('cart.items');
    $firstItem = $cartItems[0];

    // Update quantity to 2
    $updateResponse = $this->actingAs($user)->putJson('/cart/item/' . $firstItem['id'], [
        'quantity' => 2,
    ]);

    $updateResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    $updatedItem = collect($updateResponse->json('cart.items'))->firstWhere('id', $firstItem['id']);
    expect($updatedItem['quantity'])->toBe(2);
});

test('user can remove item from cart', function () {
    $user = User::factory()->create();
    $variant = getTestVariant(5);

    // Add item as user
    $addResponse = $this->actingAs($user)->postJson('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $firstItem = $addResponse->json('cart.items')[0];

    // Remove item
    $removeResponse = $this->actingAs($user)->deleteJson('/cart/item/' . $firstItem['id']);

    $removeResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    $remaining = collect($removeResponse->json('cart.items'))->firstWhere('id', $firstItem['id']);
    expect($remaining)->toBeNull();
});

test('cart merges from guest session to user upon login', function () {
    $variant = getTestVariant(5);
    $user = User::factory()->create();
    $sessionId = 'merge-test-session-' . uniqid();

    // Create guest cart directly with known session ID
    $guestCart = Cart::create(['session_id' => $sessionId, 'user_id' => null]);
    $guestCart->items()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    // Request as user with that guest cart in session
    $response = $this->actingAs($user)->withSession(['guest_cart_id' => $guestCart->id])->getJson('/cart');

    $response->assertStatus(200);
    expect($response->json('cart.item_count'))->toBe(2);

    // Guest cart should now be deleted after merge
    expect(Cart::find($guestCart->id))->toBeNull();
});
