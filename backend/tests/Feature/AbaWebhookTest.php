<?php

use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Payment\AbaPaywayService;

function createTestAbaOrder(array $attrs = []): Order
{
    $category = Category::firstOrCreate(['slug' => 'webhook-test-cat'], ['name' => 'Webhook Test']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Aba Test Product',
        'slug' => 'aba-test-' . uniqid(),
        'price' => 35.00,
        'is_active' => true,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'color' => 'Black',
        'sku' => 'ABA-TEST-' . strtoupper(uniqid()),
        'stock_quantity' => 15,
        'additional_price' => 0.00,
    ]);

    $order = Order::create(array_merge([
        'order_number' => 'HOYOS-' . strtoupper(uniqid()),
        'guest_token' => 'test-guest-token-' . uniqid(),
        'customer_name' => 'Sokha Chan',
        'customer_phone' => '012345678',
        'customer_email' => 'sokha@example.com',
        'shipping_address' => 'Street 2004, Phnom Penh',
        'province' => 'Phnom Penh',
        'delivery_notes' => 'Call before arrival',
        'subtotal' => 35.00,
        'shipping_fee' => 1.50,
        'total_amount' => 36.50,
        'order_status' => 'pending',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ], $attrs));

    OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_size' => 'M',
        'unit_price' => 35.00,
        'quantity' => 1,
        'subtotal' => 35.00,
    ]);

    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'aba_khqr',
        'transaction_id' => $order->order_number,
        'amount' => 36.50,
        'currency' => 'USD',
        'status' => Payment::STATUS_PENDING,
        'qr_string' => '00020101021229340016hoyos@ababank...',
        'md5_hash' => md5($order->order_number),
        'expires_at' => now()->addMinutes(5),
    ]);

    return $order;
}

test('webhook processes valid payment, updates order to paid and confirmed, and allows dispatch queue entry', function () {
    $order = createTestAbaOrder();
    $apiKey = config('services.aba_payway.api_key', 'D6B2E124A4441A38778A6DD7E2A45250DADAE7BD');
    $service = new AbaPaywayService(apiKey: $apiKey);

    $hash = $service->getHash($order->order_number . '0');

    $response = $this->postJson('/api/webhooks/aba', [
        'tran_id' => $order->order_number,
        'status'  => '0',
        'hash'    => $hash,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 0,
            'message' => 'Success',
        ]);

    $order->refresh();
    expect($order->payment_status)->toBe('paid');
    expect($order->order_status)->toBe('confirmed');

    $payment = $order->latestPayment;
    expect($payment->status)->toBe(Payment::STATUS_PAID);
    expect($payment->paid_at)->not->toBeNull();

    // Verify order joins dispatch queue scope (readyForFulfillment)
    $readyIds = Order::readyForFulfillment()->pluck('id')->all();
    expect($readyIds)->toContain($order->id);

    // Verify audit log entry
    $audit = AdminAuditLog::where('target_table', 'orders')
        ->where('target_id', (string) $order->id)
        ->where('action', 'confirm')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->module)->toBe('payments');
    expect($audit->new_values['payment_status'])->toBe('paid');

    // Verify status transition history
    $history = OrderStatusHistory::where('order_id', $order->id)
        ->where('new_status', 'confirmed')
        ->first();

    expect($history)->not->toBeNull();
});

test('webhook rejects invalid signature with 403 forbidden', function () {
    $order = createTestAbaOrder();

    $response = $this->postJson('/api/webhooks/aba', [
        'tran_id' => $order->order_number,
        'status'  => '0',
        'hash'    => 'invalid_tampered_hash',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'status' => 1,
            'message' => 'Invalid signature',
        ]);

    $order->refresh();
    expect($order->payment_status)->toBe('pending');
    expect($order->order_status)->toBe('pending');
});

test('webhook is idempotent and returns already processed without duplicating side effects', function () {
    $order = createTestAbaOrder([
        'payment_status' => 'paid',
        'order_status'   => 'confirmed',
    ]);

    $payment = $order->latestPayment;
    $payment->status = Payment::STATUS_PAID;
    $payment->paid_at = now();
    $payment->save();

    $apiKey = config('services.aba_payway.api_key', 'D6B2E124A4441A38778A6DD7E2A45250DADAE7BD');
    $service = new AbaPaywayService(apiKey: $apiKey);
    $hash = $service->getHash($order->order_number . '0');

    $auditCountBefore = AdminAuditLog::count();
    $historyCountBefore = OrderStatusHistory::count();

    $response = $this->postJson('/api/webhooks/aba', [
        'tran_id' => $order->order_number,
        'status'  => '0',
        'hash'    => $hash,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 0,
            'message' => 'Already processed',
        ]);

    // Verify no new audit logs or history records were added
    expect(AdminAuditLog::count())->toBe($auditCountBefore);
    expect(OrderStatusHistory::count())->toBe($historyCountBefore);
});

test('client polling endpoint returns order payment status', function () {
    $order = createTestAbaOrder([
        'payment_status' => 'pending',
    ]);

    $response = $this->getJson("/api/orders/{$order->order_number}/payment-status?token={$order->guest_token}");

    $response->assertOk()
        ->assertJson([
            'payment_status' => 'pending',
            'order_status'   => 'pending',
            'is_paid'        => false,
        ]);

    // Mark paid and poll again
    $order->payment_status = 'paid';
    $order->save();

    $responsePaid = $this->getJson("/api/orders/{$order->order_number}/payment-status?token={$order->guest_token}");
    $responsePaid->assertOk()
        ->assertJson([
            'payment_status' => 'paid',
            'is_paid'        => true,
        ]);
});
