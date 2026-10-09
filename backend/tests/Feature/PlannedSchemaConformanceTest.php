<?php

use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\QueryException;

test('order has many payments and latestPayment returns latest of many', function () {
    $order = Order::create([
        'order_number' => 'HOYOS-TEST01',
        'customer_name' => 'Test User',
        'customer_phone' => '012345678',
        'shipping_address' => 'Test St',
        'province' => 'Phnom Penh',
        'subtotal' => 50.00,
        'shipping_fee' => 1.50,
        'total_amount' => 51.50,
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);

    $payment1 = Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'aba_khqr',
        'transaction_id' => 'TXN-01',
        'amount' => 51.50,
        'currency' => 'USD',
        'status' => Payment::STATUS_EXPIRED,
        'created_at' => now()->subMinutes(10),
    ]);

    $payment2 = Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'aba_khqr',
        'transaction_id' => 'TXN-02',
        'amount' => 51.50,
        'currency' => 'USD',
        'status' => Payment::STATUS_PENDING,
        'created_at' => now(),
    ]);

    expect($order->payments)->toHaveCount(2);
    expect($order->latestPayment->id)->toBe($payment2->id);
});

test('order status history records lifecycle transitions and relations', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::create([
        'order_number' => 'HOYOS-TEST02',
        'customer_name' => 'Jane Doe',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 2004',
        'province' => 'Phnom Penh',
        'subtotal' => 30.00,
        'shipping_fee' => 1.50,
        'total_amount' => 31.50,
        'order_status' => 'confirmed',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);

    $history = OrderStatusHistory::create([
        'order_id' => $order->id,
        'admin_id' => $admin->id,
        'old_status' => null,
        'new_status' => 'confirmed',
        'notes' => 'Initial checkout',
        'created_at' => now(),
    ]);

    expect($history->order->id)->toBe($order->id);
    expect($history->admin->id)->toBe($admin->id);
    expect($order->statusHistories)->toHaveCount(1);
    expect($admin->orderStatusHistories)->toHaveCount(1);
});

test('stock adjustments records ledger entries with relations', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::create(['name' => 'Test', 'slug' => 'test-cat']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Vintage Tee',
        'slug' => 'vintage-tee',
        'price' => 25.00,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'HOYOS-TEE-M',
        'stock_quantity' => 10,
    ]);

    $order = Order::create([
        'order_number' => 'HOYOS-TEST03',
        'customer_name' => 'John Doe',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 271',
        'province' => 'Phnom Penh',
        'subtotal' => 25.00,
        'shipping_fee' => 1.50,
        'total_amount' => 26.50,
        'order_status' => 'confirmed',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);

    $adjustment = StockAdjustment::create([
        'product_variant_id' => $variant->id,
        'order_id' => $order->id,
        'admin_id' => $admin->id,
        'adjustment_type' => 'order_reserved',
        'quantity_change' => -1,
        'quantity_before' => 10,
        'quantity_after' => 9,
        'reason' => 'Order #HOYOS-TEST03 Reserved',
        'created_at' => now(),
    ]);

    expect($adjustment->productVariant->id)->toBe($variant->id);
    expect($adjustment->variant->id)->toBe($variant->id);
    expect($adjustment->order->id)->toBe($order->id);
    expect($adjustment->admin->id)->toBe($admin->id);
    expect($variant->stockAdjustments)->toHaveCount(1);
    expect($order->stockAdjustments)->toHaveCount(1);
    expect($admin->stockAdjustments)->toHaveCount(1);
});

test('admin audit log records events and serializes json snapshots', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $log = AdminAuditLog::create([
        'admin_id' => $admin->id,
        'module' => 'orders',
        'action' => 'update',
        'target_table' => 'orders',
        'target_id' => 101,
        'old_values' => ['order_status' => 'confirmed'],
        'new_values' => ['order_status' => 'processing'],
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    expect($log->admin->id)->toBe($admin->id);
    expect($log->old_values['order_status'])->toBe('confirmed');
    expect($log->new_values['order_status'])->toBe('processing');
    expect($admin->adminAuditLogs)->toHaveCount(1);
});

test('users table has account_status default to active', function () {
    $user = User::factory()->create();
    expect($user->account_status)->toBe('active');
});

test('status cancelled constants exist on shipment and payment', function () {
    expect(Shipment::STATUS_CANCELLED)->toBe('cancelled');
    expect(Payment::STATUS_CANCELLED)->toBe('cancelled');
});

test('shipments order_id unique constraint rejects duplicate shipments for same order', function () {
    $order = Order::create([
        'order_number' => 'HOYOS-TEST04',
        'customer_name' => 'Dup Order Test',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 1',
        'province' => 'Phnom Penh',
        'subtotal' => 20.00,
        'shipping_fee' => 1.50,
        'total_amount' => 21.50,
        'order_status' => 'confirmed',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);

    Shipment::create([
        'order_id' => $order->id,
        'carrier' => 'J&T Express',
        'status' => Shipment::STATUS_ORDER_CONFIRMED,
    ]);

    expect(fn() => Shipment::create([
        'order_id' => $order->id,
        'carrier' => 'J&T Express',
        'status' => Shipment::STATUS_PROCESSING,
    ]))->toThrow(QueryException::class);
});
