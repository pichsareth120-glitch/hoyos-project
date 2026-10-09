<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

test('aba khqr payment screen displays authentic Bakong details and 5-min timer', function () {
    $order = Order::factory()->create([
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
        'guest_token' => 'guest-khqr-token-' . uniqid(),
    ]);

    $response = $this->get('/payment/' . $order->order_number . '?token=' . $order->guest_token);

    $response->assertStatus(200);
    $response->assertSee('KHQR');
    $response->assertSee('HOYOS CLOTHING');
    $response->assertSee('Simulate Instant ABA Payment Approval');
});

test('polling status endpoint reports pending then paid after simulation', function () {
    $order = Order::factory()->create([
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
        'guest_token' => 'guest-poll-token-' . uniqid(),
    ]);

    // Check status before payment (controller initializes payment)
    $this->get('/payment/' . $order->order_number . '?token=' . $order->guest_token);

    $statusResponse = $this->getJson('/payment/' . $order->order_number . '/status?token=' . $order->guest_token);
    $statusResponse->assertStatus(200)
        ->assertJson(['is_paid' => false, 'status' => 'pending']);

    // Simulate ABA payment approval
    $simResponse = $this->postJson('/payment/' . $order->order_number . '/simulate?token=' . $order->guest_token);
    $simResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    // Check status after simulation
    $statusAfter = $this->getJson('/payment/' . $order->order_number . '/status?token=' . $order->guest_token);
    $statusAfter->assertStatus(200)
        ->assertJson(['is_paid' => true, 'status' => 'paid']);

    $order->refresh();
    expect($order->payment_status)->toBe('paid');
    expect($order->order_status)->toBe('confirmed');
});

test('authenticated user can poll payment status without guest token', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'guest_token' => null,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);

    // Initialize payment
    $this->actingAs($user)->get('/payment/' . $order->order_number);

    // Poll via web route without token
    $webResponse = $this->actingAs($user)->getJson('/payment/' . $order->order_number . '/status');
    $webResponse->assertStatus(200)
        ->assertJson([
            'is_paid' => false,
            'payment_status' => 'pending',
        ]);

    // Poll via api route without token
    $apiResponse = $this->actingAs($user)->getJson('/api/orders/' . $order->order_number . '/payment-status');
    $apiResponse->assertStatus(200)
        ->assertJson([
            'is_paid' => false,
            'payment_status' => 'pending',
        ]);
});

test('unauthenticated user cannot poll another user order status without token', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'guest_token' => null,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);

    $response = $this->getJson('/payment/' . $order->order_number . '/status');
    $response->assertStatus(403);

    $apiResponse = $this->getJson('/api/orders/' . $order->order_number . '/payment-status');
    $apiResponse->assertStatus(403);
});

