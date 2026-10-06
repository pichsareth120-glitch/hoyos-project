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
