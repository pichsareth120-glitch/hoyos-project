<?php

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Payment\AbaPaywayService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

function getDemoTestVariant(int $minStock = 10): ProductVariant
{
    return ProductVariant::where('stock_quantity', '>=', $minStock)->first()
        ?: ProductVariant::factory()->create(['stock_quantity' => $minStock + 10]);
}

test('demo mode allows checkout with aba_khqr without making external http calls', function () {
    Config::set('hoyos.demo_mode', true);
    Config::set('hoyos.allow_payment_simulation', true);
    Http::fake();

    $user = User::factory()->create();
    $variant = getDemoTestVariant(5);

    // Add item to cart
    $this->actingAs($user)->postJson('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    // Submit checkout with aba_khqr
    $response = $this->actingAs($user)->post('/checkout', [
        'customer_name' => 'Demo Customer',
        'customer_phone' => '012888999',
        'customer_email' => 'demo@example.com',
        'province' => 'Phnom Penh',
        'shipping_address' => '123 Demo Street, BKK1',
        'payment_method' => 'aba_khqr',
    ]);

    $order = Order::where('customer_phone', '012888999')->latest()->first();
    expect($order)->not->toBeNull();
    expect($order->payment_method)->toBe('aba_khqr');
    expect($order->payment_status)->toBe('pending');

    // Confirm redirected to payment screen
    $response->assertRedirect(route('payment.show', ['orderNumber' => $order->order_number]));

    // Assert that NO outbound HTTP requests were made to ABA PayWay or external APIs
    Http::assertNothingSent();

    $payment = $order->latestPayment;
    expect($payment)->not->toBeNull();
    expect($payment->status)->toBe('pending');
    expect($payment->qr_string)->not->toBeEmpty();
    expect($payment->payload['demo_mode'])->toBeTrue();
    expect($payment->payload['is_simulated'])->toBeTrue();
});

test('demo mode rejects cod payment method server-side', function () {
    Config::set('hoyos.demo_mode', true);

    $user = User::factory()->create();
    $variant = getDemoTestVariant(5);
    $initialStock = $variant->stock_quantity;

    $this->actingAs($user)->postJson('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    // Attempting COD in demo mode must fail validation
    $response = $this->actingAs($user)->post('/checkout', [
        'customer_name' => 'Demo User',
        'customer_phone' => '012777888',
        'province' => 'Phnom Penh',
        'shipping_address' => 'St 2004, Phnom Penh',
        'payment_method' => 'cod',
    ]);

    $response->assertSessionHasErrors(['payment_method']);

    // No order should have been created
    $order = Order::where('customer_phone', '012777888')->first();
    expect($order)->toBeNull();

    // Stock must not be touched
    $variant->refresh();
    expect($variant->stock_quantity)->toBe($initialStock);
});

test('demo mode rejects arbitrary invalid payment methods server-side', function () {
    Config::set('hoyos.demo_mode', true);

    $user = User::factory()->create();
    $variant = getDemoTestVariant(5);

    $this->actingAs($user)->postJson('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)->post('/checkout', [
        'customer_name' => 'Demo User',
        'customer_phone' => '012666555',
        'province' => 'Phnom Penh',
        'shipping_address' => 'St 123, Phnom Penh',
        'payment_method' => 'credit_card',
    ]);

    $response->assertSessionHasErrors(['payment_method']);
});

test('demo mode payment simulation verifies order and sets simulation metadata', function () {
    Config::set('hoyos.demo_mode', true);
    Config::set('hoyos.allow_payment_simulation', true);
    Http::fake();

    $order = Order::factory()->create([
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
        'order_status' => 'confirmed',
        'guest_token' => 'guest-demo-sim-' . uniqid(),
    ]);

    // View payment screen to ensure payment record exists
    $this->get('/payment/' . $order->order_number . '?token=' . $order->guest_token);

    // Call simulate endpoint
    $response = $this->postJson('/payment/' . $order->order_number . '/simulate?token=' . $order->guest_token);
    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $order->refresh();
    expect($order->payment_status)->toBe('paid');
    expect($order->order_status)->toBe('confirmed');

    $payment = $order->latestPayment;
    expect($payment->status)->toBe('paid');
    expect($payment->payload['is_simulated'])->toBeTrue();
    expect($payment->payload['external_reference'])->toStartWith('DEMO-SIMULATION-');

    // Confirm UI receipt reflects simulated payment
    $receipt = $this->get('/orders/' . $order->order_number . '?token=' . $order->guest_token);
    $receipt->assertStatus(200);
    $receipt->assertSee('Simulated Demo (No Charge)');

    // Ensure still no outbound HTTP calls
    Http::assertNothingSent();
});

test('demo mode payment simulation enforces order ownership access controls', function () {
    Config::set('hoyos.demo_mode', true);
    Config::set('hoyos.allow_payment_simulation', true);

    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'guest_token' => null,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);

    // Initialize payment for order
    app(AbaPaywayService::class)->createPurchase($order);

    // Stranger cannot simulate owner's payment
    $response = $this->actingAs($stranger)->postJson('/payment/' . $order->order_number . '/simulate');
    $response->assertStatus(403);

    // Guest cannot simulate without valid guest token
    $guestOrder = Order::factory()->create([
        'user_id' => null,
        'guest_token' => 'secret-guest-token-123',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);
    app(AbaPaywayService::class)->createPurchase($guestOrder);

    $wrongGuestResponse = $this->postJson('/payment/' . $guestOrder->order_number . '/simulate?token=wrong-token');
    $wrongGuestResponse->assertStatus(403);

    // Legitimate owner CAN simulate
    $ownerResponse = $this->actingAs($owner)->postJson('/payment/' . $order->order_number . '/simulate');
    $ownerResponse->assertStatus(200)->assertJson(['success' => true]);
});

test('repeated payment simulation requests are idempotent', function () {
    Config::set('hoyos.demo_mode', true);
    Config::set('hoyos.allow_payment_simulation', true);

    $order = Order::factory()->create([
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
        'guest_token' => 'idempotent-token-' . uniqid(),
    ]);

    $this->get('/payment/' . $order->order_number . '?token=' . $order->guest_token);

    // First simulation
    $res1 = $this->postJson('/payment/' . $order->order_number . '/simulate?token=' . $order->guest_token);
    $res1->assertStatus(200);

    // Repeated simulation
    $res2 = $this->postJson('/payment/' . $order->order_number . '/simulate?token=' . $order->guest_token);
    $res2->assertStatus(200);

    $order->refresh();
    expect($order->payment_status)->toBe('paid');
});

test('demo mode cannot simulate payment on a cancelled order', function () {
    Config::set('hoyos.demo_mode', true);
    Config::set('hoyos.allow_payment_simulation', true);

    $order = Order::factory()->create([
        'payment_method' => 'aba_khqr',
        'payment_status' => 'expired',
        'order_status' => 'cancelled',
        'guest_token' => 'cancelled-token-' . uniqid(),
    ]);

    $response = $this->postJson('/payment/' . $order->order_number . '/simulate?token=' . $order->guest_token);
    $response->assertStatus(422);
});

test('normal mode still allows cod when demo mode is false', function () {
    Config::set('hoyos.demo_mode', false);

    $user = User::factory()->create();
    $variant = getDemoTestVariant(5);

    $this->actingAs($user)->postJson('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)->post('/checkout', [
        'customer_name' => 'Normal User',
        'customer_phone' => '012555444',
        'province' => 'Phnom Penh',
        'shipping_address' => 'St Normal, Phnom Penh',
        'payment_method' => 'cod',
    ]);

    $order = Order::where('customer_phone', '012555444')->first();
    expect($order)->not->toBeNull();
    expect($order->payment_method)->toBe('cod');
    $response->assertRedirect(route('orders.show', ['orderNumber' => $order->order_number]));
});

test('demo mode emits non-payable qr payload and no real merchant details', function () {
    Config::set('hoyos.demo_mode', true);

    $order = Order::factory()->create([
        'order_number' => 'HOYOS-DEMO-999999',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
        'total_amount' => 45.00,
        'guest_token' => 'demo-token-999',
    ]);

    $service = new AbaPaywayService(isDemoMode: true);
    $payment = $service->createPurchase($order);

    expect($payment->qr_string)->toStartWith('DEMO-NON-PAYABLE-TRANSACTION:');
    // Ensure no real personal name or account ID is leaked
    expect($payment->qr_string)->not->toContain('Sareth Pich');
    expect($payment->qr_string)->not->toContain('abaakhppxxx@abaa');
    expect($payment->payload['merchant_id'])->toBe('DEMO-HOYOS');
    expect($payment->payload['merchant_name'])->toContain('NON-PAYABLE');
    expect($payment->payload['abapay_deeplink'])->toBeNull();

    // Verify view renders non-payable demo card
    $response = $this->get('/payment/' . $order->order_number . '?token=' . $order->guest_token);
    $response->assertStatus(200);
    $response->assertSee('NON-PAYABLE DEMO');
    $response->assertSee('Do Not Scan');
    $response->assertDontSee('Open in ABA Mobile App');
});

test('demo mode rejects external webhook callbacks as proof of payment', function () {
    Config::set('hoyos.demo_mode', true);

    $response = $this->postJson('/api/webhooks/aba', [
        'tran_id' => 'HOYOS-DEMO-123456',
        'status' => '0',
    ]);

    $response->assertStatus(403);
    $response->assertJson([
        'status' => 1,
        'message' => 'Webhooks are disabled in demo mode. Please use simulated payment approval.',
    ]);
});

