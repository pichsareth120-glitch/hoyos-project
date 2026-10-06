<?php

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;

test('guest with token can view order receipt and tracking timeline', function () {
    $order = Order::factory()->create([
        'guest_token' => 'guest-test-token-' . uniqid(),
    ]);

    $order->shipment()->create([
        'carrier' => 'J&T Express',
        'tracking_number' => 'JT9988776655KH',
        'status' => 'shipped',
    ]);

    $response = $this->get('/orders/' . $order->order_number . '?token=' . $order->guest_token);
    $response->assertStatus(200);
    $response->assertSee($order->order_number);

    $trackResponse = $this->get('/orders/' . $order->order_number . '/track?token=' . $order->guest_token);
    $trackResponse->assertStatus(200);
    $trackResponse->assertSee('J&T Express');
    $trackResponse->assertSee('Fulfillment Progress');
});

test('user cannot view another users order without authorization', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $userA->id,
        'guest_token' => null,
    ]);

    // User B tries to view User A's order
    $response = $this->actingAs($userB)->get('/orders/' . $order->order_number);
    $response->assertStatus(403);

    // Guest without token tries to view
    $guestResponse = $this->get('/orders/' . $order->order_number);
    $guestResponse->assertStatus(403);
});

test('public order lookup verifies order number and customer phone', function () {
    $order = Order::factory()->create([
        'guest_token' => 'lookup-token-' . uniqid(),
        'customer_phone' => '012999888',
    ]);

    $response = $this->post('/track-order', [
        'order_number' => $order->order_number,
        'customer_phone' => $order->customer_phone,
    ]);

    $response->assertRedirect(route('orders.track', [
        'orderNumber' => $order->order_number,
        'token' => $order->guest_token,
    ]));
});

test('admin can enter jt tracking code and advance fulfillment status', function () {
    $admin = User::factory()->admin()->create();

    $order = Order::factory()->create();

    $response = $this->actingAs($admin)->put('/admin/shipments/' . $order->id, [
        'tracking_number' => 'JT8877665544KH',
        'status' => 'shipped',
    ]);

    $response->assertSessionHas('success');

    $order = $order->fresh(['shipment']);
    expect($order->shipment)->not->toBeNull();
    expect($order->shipment->carrier)->toBe('J&T Express');
    expect($order->shipment->tracking_number)->toBe('JT8877665544KH');
    expect($order->shipment->status)->toBe('shipped');
    expect($order->shipment->shipped_at)->not->toBeNull();
});
