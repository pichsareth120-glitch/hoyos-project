<?php

use App\Actions\CancelOrderAction;
use App\Actions\CreateOrderAction;
use App\Actions\RestoreStockAction;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\StockAdjustment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-10-07 14:00:00'));
});

afterEach(function () {
    Carbon::setTestNow();
});

test('stock 1, first order succeeds, second fails, stock is never negative', function () {
    $category = Category::create(['name' => 'Tops', 'slug' => 'tops']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Vintage Dark Tee',
        'slug' => 'vintage-dark-tee',
        'price' => 35.00,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'HOYOS-VDT-M',
        'stock_quantity' => 1,
    ]);

    $action = app(CreateOrderAction::class);

    // Cart 1: Quantity 1
    $cart1 = Cart::create(['session_id' => 'session_1']);
    CartItem::create([
        'cart_id' => $cart1->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $checkoutData = [
        'customer_name' => 'Customer One',
        'customer_phone' => '012345678',
        'customer_email' => 'one@example.com',
        'shipping_address' => 'Street 2004',
        'province' => 'Phnom Penh',
        'payment_method' => 'cod',
    ];

    // First order should succeed
    $order1 = $action->execute($cart1, $checkoutData);
    expect($order1)->not->toBeNull();
    expect($variant->fresh()->stock_quantity)->toBe(0);

    // Cart 2: Quantity 1 for same variant (now 0 stock)
    $cart2 = Cart::create(['session_id' => 'session_2']);
    CartItem::create([
        'cart_id' => $cart2->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    // Second order must fail with insufficient stock error
    expect(fn() => $action->execute($cart2, $checkoutData))
        ->toThrow(Exception::class, "Product Vintage Dark Tee (Size M) is out of stock.");

    // Stock must remain 0 and never be negative
    expect($variant->fresh()->stock_quantity)->toBe(0);
});

test('stock_adjustments row is written with correct before/after', function () {
    $category = Category::create(['name' => 'Bottoms', 'slug' => 'bottoms']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Cargo Pants',
        'slug' => 'cargo-pants',
        'price' => 50.00,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'L',
        'sku' => 'HOYOS-CP-L',
        'stock_quantity' => 5,
    ]);

    $cart = Cart::create(['session_id' => 'session_adj']);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $action = app(CreateOrderAction::class);
    $order = $action->execute($cart, [
        'customer_name' => 'Adjustment Test',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 105',
        'province' => 'Phnom Penh',
        'payment_method' => 'cod',
    ]);

    $adjustment = StockAdjustment::where('order_id', $order->id)->first();
    expect($adjustment)->not->toBeNull();
    expect($adjustment->adjustment_type)->toBe('order_reserved');
    expect($adjustment->quantity_change)->toBe(-2);
    expect($adjustment->quantity_before)->toBe(5);
    expect($adjustment->quantity_after)->toBe(3);
    expect($adjustment->admin_id)->toBeNull();
    expect($adjustment->reason)->toContain($order->order_number);
});

test('RestoreStockAction called twice restores once', function () {
    $category = Category::create(['name' => 'Jackets', 'slug' => 'jackets']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Varsity Jacket',
        'slug' => 'varsity-jacket',
        'price' => 80.00,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'XL',
        'sku' => 'HOYOS-VJ-XL',
        'stock_quantity' => 0,
    ]);

    $order = Order::create([
        'order_number' => 'HOYOS-RESTORE1',
        'customer_name' => 'Restore User',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 310',
        'province' => 'Phnom Penh',
        'subtotal' => 80.00,
        'shipping_fee' => 0.00,
        'total_amount' => 80.00,
        'order_status' => 'confirmed',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);

    $order->items()->create([
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_size' => $variant->size,
        'unit_price' => 80.00,
        'quantity' => 2,
        'subtotal' => 160.00,
    ]);

    $restoreAction = app(RestoreStockAction::class);

    // Call first time: should restore 2 units (0 -> 2)
    $restoreAction->execute($order, 'order_cancelled');
    expect($variant->fresh()->stock_quantity)->toBe(2);
    expect(StockAdjustment::where('order_id', $order->id)->count())->toBe(1);

    // Call second time: should do nothing (idempotent)
    $restoreAction->execute($order, 'order_cancelled');
    expect($variant->fresh()->stock_quantity)->toBe(2);
    expect(StockAdjustment::where('order_id', $order->id)->count())->toBe(1);
});

test('command marks payment expired but does NOT cancel before the grace period', function () {
    $order = Order::create([
        'order_number' => 'HOYOS-EXP01',
        'customer_name' => 'Expiry User',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 271',
        'province' => 'Phnom Penh',
        'subtotal' => 45.00,
        'shipping_fee' => 0.00,
        'total_amount' => 45.00,
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);

    $payment = Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'aba_khqr',
        'transaction_id' => 'TXN-EXP-01',
        'amount' => 45.00,
        'currency' => 'USD',
        'status' => Payment::STATUS_PENDING,
        'expires_at' => Carbon::now()->subMinutes(2), // Expired 2 minutes ago (well within 15-min grace)
    ]);

    $this->artisan('hoyos:check-payment-expiries')->assertSuccessful();

    // Payment and order payment_status should be expired
    expect($payment->fresh()->status)->toBe(Payment::STATUS_EXPIRED);
    expect($order->fresh()->payment_status)->toBe(Payment::STATUS_EXPIRED);

    // Order remains confirmed and is NOT cancelled
    expect($order->fresh()->order_status)->toBe('confirmed');
    expect(StockAdjustment::where('order_id', $order->id)->count())->toBe(0);
});

test('command cancels after the grace period: stock restored once, shipment cancelled, history row written', function () {
    $category = Category::create(['name' => 'Hoodies', 'slug' => 'hoodies']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Heavyweight Hoodie',
        'slug' => 'heavyweight-hoodie',
        'price' => 60.00,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'L',
        'sku' => 'HOYOS-HH-L',
        'stock_quantity' => 0,
    ]);

    $order = Order::create([
        'order_number' => 'HOYOS-EXP02',
        'customer_name' => 'Grace Expired User',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 63',
        'province' => 'Phnom Penh',
        'subtotal' => 60.00,
        'shipping_fee' => 0.00,
        'total_amount' => 60.00,
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'expired',
    ]);

    $order->items()->create([
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_size' => $variant->size,
        'unit_price' => 60.00,
        'quantity' => 1,
        'subtotal' => 60.00,
    ]);

    $shipment = Shipment::create([
        'order_id' => $order->id,
        'carrier' => 'J&T Express',
        'status' => Shipment::STATUS_ORDER_CONFIRMED,
    ]);

    // Payment expired 16 minutes ago (past 15-minute default grace period)
    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'aba_khqr',
        'transaction_id' => 'TXN-EXP-02',
        'amount' => 60.00,
        'currency' => 'USD',
        'status' => Payment::STATUS_EXPIRED,
        'expires_at' => Carbon::now()->subMinutes(16),
    ]);

    $this->artisan('hoyos:check-payment-expiries')->assertSuccessful();

    // Order and shipment should be cancelled
    expect($order->fresh()->order_status)->toBe('cancelled');
    expect($shipment->fresh()->status)->toBe(Shipment::STATUS_CANCELLED);

    // Stock should be restored once
    expect($variant->fresh()->stock_quantity)->toBe(1);
    expect(StockAdjustment::where('order_id', $order->id)->where('adjustment_type', 'payment_expired')->count())->toBe(1);

    // Order status history recorded
    $history = OrderStatusHistory::where('order_id', $order->id)->where('new_status', 'cancelled')->first();
    expect($history)->not->toBeNull();
    expect($history->admin_id)->toBeNull(); // Automated system cancellation
});

test('retry inside grace creates a new payment and sets payment_status pending', function () {
    $order = Order::create([
        'order_number' => 'HOYOS-RETRY01',
        'guest_token' => 'token_retry_01',
        'customer_name' => 'Retry Tester',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 51',
        'province' => 'Phnom Penh',
        'subtotal' => 40.00,
        'shipping_fee' => 0.00,
        'total_amount' => 40.00,
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'expired',
    ]);

    $oldPayment = Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'aba_khqr',
        'transaction_id' => 'TXN-OLD-01',
        'amount' => 40.00,
        'currency' => 'USD',
        'status' => Payment::STATUS_EXPIRED,
        'expires_at' => Carbon::now()->subMinutes(5), // Expired 5 mins ago, inside 15 min grace
    ]);

    $response = $this->postJson("/payment/{$order->order_number}/retry?token={$order->guest_token}");
    $response->assertSuccessful();

    $order->refresh();
    expect($order->payment_status)->toBe('pending');
    expect($order->payments)->toHaveCount(2);

    // Old payment remains expired
    expect($oldPayment->fresh()->status)->toBe(Payment::STATUS_EXPIRED);

    // New payment is pending
    $newPayment = $order->latestPayment;
    expect($newPayment->id)->not->toBe($oldPayment->id);
    expect($newPayment->status)->toBe(Payment::STATUS_PENDING);
});

test('retry after cancellation is rejected; another user cannot retry someone elses order', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $order = Order::create([
        'order_number' => 'HOYOS-RETRY02',
        'user_id' => $userA->id,
        'customer_name' => 'User A',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 1',
        'province' => 'Phnom Penh',
        'subtotal' => 20.00,
        'shipping_fee' => 0.00,
        'total_amount' => 20.00,
        'order_status' => 'cancelled',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'expired',
    ]);

    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'aba_khqr',
        'transaction_id' => 'TXN-OLD-02',
        'amount' => 20.00,
        'currency' => 'USD',
        'status' => Payment::STATUS_EXPIRED,
        'expires_at' => Carbon::now()->subMinutes(2),
    ]);

    // User B attempts to retry User A's order -> 403 Forbidden
    $this->actingAs($userB)
        ->postJson("/payment/{$order->order_number}/retry")
        ->assertStatus(403);

    // User A attempts to retry an order that is already cancelled -> 422 Unprocessable
    $this->actingAs($userA)
        ->postJson("/payment/{$order->order_number}/retry")
        ->assertStatus(422);
});

test('cancelling a shipped order throws and changes nothing', function () {
    $order = Order::create([
        'order_number' => 'HOYOS-SHIPPED01',
        'customer_name' => 'Shipped User',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 21',
        'province' => 'Phnom Penh',
        'subtotal' => 50.00,
        'shipping_fee' => 0.00,
        'total_amount' => 50.00,
        'order_status' => 'shipped',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);

    $cancelAction = app(CancelOrderAction::class);

    expect(fn() => $cancelAction->execute($order))
        ->toThrow(DomainException::class);

    expect($order->fresh()->order_status)->toBe('shipped');
});

test('admin cannot update a cancelled orders shipment', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $order = Order::create([
        'order_number' => 'HOYOS-CANCSHIP01',
        'customer_name' => 'Cancelled Shipment Test',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 99',
        'province' => 'Phnom Penh',
        'subtotal' => 30.00,
        'shipping_fee' => 0.00,
        'total_amount' => 30.00,
        'order_status' => 'cancelled',
        'payment_method' => 'cod',
        'payment_status' => 'cancelled',
    ]);

    Shipment::create([
        'order_id' => $order->id,
        'carrier' => 'J&T Express',
        'status' => Shipment::STATUS_CANCELLED,
    ]);

    $response = $this->actingAs($admin)->put("/admin/shipments/{$order->id}", [
        'status' => 'shipped',
        'tracking_number' => 'JT999999999KH',
    ]);

    $response->assertSessionHas('error');
    expect($order->shipment->fresh()->status)->toBe(Shipment::STATUS_CANCELLED);
});

test('simulator returns 404 when disabled', function () {
    config(['hoyos.allow_payment_simulation' => false]);

    $order = Order::create([
        'order_number' => 'HOYOS-SIM01',
        'guest_token' => 'guest_token_sim_01',
        'customer_name' => 'Sim User',
        'customer_phone' => '012345678',
        'shipping_address' => 'Street 10',
        'province' => 'Phnom Penh',
        'subtotal' => 25.00,
        'shipping_fee' => 0.00,
        'total_amount' => 25.00,
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);

    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'aba_khqr',
        'transaction_id' => 'TXN-SIM-01',
        'amount' => 25.00,
        'currency' => 'USD',
        'status' => Payment::STATUS_PENDING,
        'expires_at' => Carbon::now()->addMinutes(5),
    ]);

    $response = $this->postJson("/payment/{$order->order_number}/simulate?token={$order->guest_token}");
    $response->assertStatus(404);
});

test('database level check constraint rejects negative stock_quantity on postgresql', function () {
    if (DB::getDriverName() !== 'pgsql') {
        test()->markTestSkipped('Database-level CHECK constraint on product_variants requires PostgreSQL driver.');
    }

    $category = Category::create(['name' => 'Test', 'slug' => 'test-cat-pg']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'PG Test Product',
        'slug' => 'pg-test-product',
        'price' => 10.00,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'HOYOS-PG-CHECK',
        'stock_quantity' => 1,
    ]);

    expect(fn() => DB::statement('UPDATE product_variants SET stock_quantity = -1 WHERE id = ?', [$variant->id]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

