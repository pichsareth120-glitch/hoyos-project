<?php

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;

function getCheckoutTestVariant(int $minStock = 10): ProductVariant
{
    return ProductVariant::where('stock_quantity', '>=', $minStock)->first() 
        ?: ProductVariant::factory()->create(['stock_quantity' => $minStock + 10]);
}

test('empty cart redirects away from checkout', function () {
    $response = $this->get('/checkout');

    $response->assertRedirect(route('home'));
});

test('authenticated user can place order with Cash on Delivery', function () {
    $user = User::factory()->create();
    $variant = getCheckoutTestVariant(10);
    $initialStock = $variant->stock_quantity;

    // Add item to cart
    $this->actingAs($user)->postJson('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $response = $this->actingAs($user)->post('/checkout', [
        'customer_name' => 'Sokha Meng',
        'customer_phone' => '012998877',
        'customer_email' => 'sokha@gmail.com',
        'province' => 'Phnom Penh',
        'shipping_address' => 'House 12B, St 271, Sangkat Boeng Tumpun',
        'payment_method' => 'cod',
        'delivery_notes' => 'Ring doorbell upon arrival',
    ]);

    $order = Order::where('customer_phone', '012998877')->latest()->first();
    expect($order)->not->toBeNull();
    expect($order->payment_method)->toBe('cod');
    expect($order->payment_status)->toBe('pending');
    expect($order->order_status)->toBe('confirmed');
    expect($order->items()->count())->toBe(1);

    // Stock should be decremented
    $variant->refresh();
    expect($variant->stock_quantity)->toBe($initialStock - 2);

    $response->assertRedirect(route('orders.show', ['orderNumber' => $order->order_number]));
});

test('buyer selecting ABA KHQR is redirected to KHQR payment screen', function () {
    $user = User::factory()->create();
    $variant = getCheckoutTestVariant(5);

    // Add to cart
    $this->actingAs($user)->postJson('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)->post('/checkout', [
        'customer_name' => 'Dara Rath',
        'customer_phone' => '098112233',
        'province' => 'Siem Reap',
        'shipping_address' => 'Wat Bo Village, Sala Kamreuk',
        'payment_method' => 'aba_khqr',
    ]);

    $order = Order::where('customer_phone', '098112233')->latest()->first();
    expect($order)->not->toBeNull();
    expect($order->payment_method)->toBe('aba_khqr');

    $response->assertRedirect(route('payment.show', ['orderNumber' => $order->order_number]));
});
