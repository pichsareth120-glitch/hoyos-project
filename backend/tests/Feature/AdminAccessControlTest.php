<?php

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;

test('guest visiting /admin/shipments is redirected to login', function () {
    $response = $this->get('/admin/shipments');

    $response->assertRedirect(route('login'));
});

test('customer gets 403 on GET /admin/shipments and on PUT /admin/shipments/{order}', function () {
    $customer = User::factory()->create([
        'role' => 'customer',
    ]);
    $order = Order::factory()->create();

    // GET attempt
    $getResponse = $this->actingAs($customer)->get('/admin/shipments');
    $getResponse->assertStatus(403);

    // PUT attempt
    $putResponse = $this->actingAs($customer)->put('/admin/shipments/' . $order->id, [
        'tracking_number' => 'JT999999999KH',
        'status' => 'shipped',
    ]);
    $putResponse->assertStatus(403);
});

test('admin gets 200 on GET /admin/shipments', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/shipments');

    $response->assertStatus(200);
});

test('customer cannot update a shipment and database remains unchanged', function () {
    $customer = User::factory()->create([
        'role' => 'customer',
    ]);
    $order = Order::factory()->create();

    $shipment = Shipment::create([
        'order_id' => $order->id,
        'carrier' => 'J&T Express',
        'tracking_number' => null,
        'status' => 'order_confirmed',
    ]);

    $response = $this->actingAs($customer)->put('/admin/shipments/' . $order->id, [
        'tracking_number' => 'UNAUTHORIZED-TRACKING-999',
        'status' => 'delivered',
    ]);

    $response->assertStatus(403);

    $shipment->refresh();
    expect($shipment->tracking_number)->toBeNull()
        ->and($shipment->status)->toBe('order_confirmed');
});
