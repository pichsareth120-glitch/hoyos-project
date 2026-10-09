<?php

use App\Actions\CancelOrderAction;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\ShipmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-10-07 14:00:00'));
});

afterEach(function () {
    Carbon::setTestNow();
});

function createTestProductWithVariant(int $stock = 10): array
{
    $category = Category::firstOrCreate(['slug' => 'hoodies'], ['name' => 'Hoodies']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Goth Cyber Hoodie',
        'slug' => 'goth-cyber-hoodie-' . uniqid(),
        'price' => 50.00,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'L',
        'sku' => 'HOYOS-GCH-L-' . uniqid(),
        'stock_quantity' => $stock,
    ]);

    return [$product, $variant];
}

function createTestOrder(array $attributes = [], int $itemQuantity = 1, int $initialStock = 10): array
{
    [$product, $variant] = createTestProductWithVariant($initialStock);

    $order = Order::create(array_merge([
        'order_number' => 'HOYOS-' . strtoupper(uniqid()),
        'customer_name' => 'Test Customer',
        'customer_phone' => '012999888',
        'customer_email' => 'customer@example.com',
        'shipping_address' => 'Street 101, BKK1',
        'province' => 'Phnom Penh',
        'subtotal' => 50.00 * $itemQuantity,
        'shipping_fee' => 0.00,
        'total_amount' => 50.00 * $itemQuantity,
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ], $attributes));

    $item = OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_size' => $variant->size,
        'unit_price' => 50.00,
        'quantity' => $itemQuantity,
        'subtotal' => 50.00 * $itemQuantity,
    ]);

    // Initial deduction of stock for the order
    $variant->decrement('stock_quantity', $itemQuantity);

    StockAdjustment::create([
        'product_variant_id' => $variant->id,
        'order_id' => $order->id,
        'adjustment_type' => 'order_reserved',
        'quantity_change' => -$itemQuantity,
        'quantity_before' => $initialStock,
        'quantity_after' => $initialStock - $itemQuantity,
        'reason' => "Order #{$order->order_number} Reserved",
        'created_at' => now(),
    ]);

    $shipment = Shipment::create([
        'order_id' => $order->id,
        'carrier' => 'J&T Express',
        'tracking_number' => null,
        'status' => 'order_confirmed',
    ]);

    $payment = Payment::create([
        'order_id' => $order->id,
        'payment_method' => $order->payment_method,
        'transaction_id' => 'TXN-' . uniqid(),
        'amount' => $order->total_amount,
        'currency' => 'USD',
        'status' => $order->payment_status,
        'created_at' => now(),
    ]);

    return [$order, $variant, $shipment, $payment];
}

/* =========================================================================
 * 1. ACCESS CONTROL TESTS (Guest vs Customer vs Admin)
 * ========================================================================= */

test('guest is redirected to login on all new admin routes', function () {
    [$order, , , $payment] = createTestOrder();

    $this->get('/admin')->assertRedirect(route('login'));
    $this->get('/admin/orders')->assertRedirect(route('login'));
    $this->get('/admin/orders/' . $order->id)->assertRedirect(route('login'));
    $this->put('/admin/orders/' . $order->id . '/processing')->assertRedirect(route('login'));
    $this->post('/admin/orders/' . $order->id . '/cancel', ['reason' => 'Test reason'])->assertRedirect(route('login'));
    $this->get('/admin/payments')->assertRedirect(route('login'));
    $this->post('/admin/payments/' . $payment->id . '/mark-paid')->assertRedirect(route('login'));
    $this->post('/admin/payments/' . $payment->id . '/refund', ['notes' => 'Test refund note'])->assertRedirect(route('login'));
});

test('customer gets 403 forbidden on all new admin routes', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    [$order, , , $payment] = createTestOrder();

    $this->actingAs($customer)->get('/admin')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/orders')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/orders/' . $order->id)->assertStatus(403);
    $this->actingAs($customer)->put('/admin/orders/' . $order->id . '/processing')->assertStatus(403);
    $this->actingAs($customer)->post('/admin/orders/' . $order->id . '/cancel', ['reason' => 'Test reason'])->assertStatus(403);
    $this->actingAs($customer)->get('/admin/payments')->assertStatus(403);
    $this->actingAs($customer)->post('/admin/payments/' . $payment->id . '/mark-paid')->assertStatus(403);
    $this->actingAs($customer)->post('/admin/payments/' . $payment->id . '/refund', ['notes' => 'Test refund note'])->assertStatus(403);
});

test('admin can access /admin, /admin/orders, and /admin/payments', function () {
    $admin = User::factory()->admin()->create();
    [$order, , , $payment] = createTestOrder();

    // /admin renders the dashboard
    $this->actingAs($admin)->get('/admin')->assertStatus(200);

    $this->actingAs($admin)->get('/admin/orders')->assertStatus(200);
    $this->actingAs($admin)->get('/admin/orders/' . $order->id)->assertStatus(200);
    $this->actingAs($admin)->get('/admin/payments')->assertStatus(200);
});

/* =========================================================================
 * 2. ORDER CANCELLATION & STOCK RESTORATION TESTS
 * ========================================================================= */

test('admin can cancel confirmed order: stock restored once, shipment cancelled, history + audit rows written, second cancel rejected', function () {
    $admin = User::factory()->admin()->create();
    [$order, $variant, $shipment] = createTestOrder(['order_status' => 'confirmed'], 2, 10);

    // Initial check: stock is 8 (10 - 2)
    $variant->refresh();
    expect($variant->stock_quantity)->toBe(8);

    // Admin cancels order
    $response = $this->actingAs($admin)->post('/admin/orders/' . $order->id . '/cancel', [
        'reason' => 'Customer requested order cancellation',
    ]);

    $response->assertSessionHas('success');

    $order->refresh();
    $variant->refresh();
    $shipment->refresh();

    // Assert order and shipment cancelled
    expect($order->order_status)->toBe('cancelled')
        ->and($shipment->status)->toBe('cancelled');

    // Assert stock restored once
    expect($variant->stock_quantity)->toBe(10);

    // Assert order_status_history recorded
    $history = OrderStatusHistory::where('order_id', $order->id)->where('new_status', 'cancelled')->first();
    expect($history)->not->toBeNull()
        ->and($history->admin_id)->toBe($admin->id)
        ->and($history->notes)->toBe('Customer requested order cancellation');

    // Assert admin audit log recorded
    $this->assertDatabaseHas('admin_audit_logs', [
        'admin_id' => $admin->id,
        'module' => 'orders',
        'action' => 'cancel',
        'target_table' => 'orders',
        'target_id' => $order->id,
    ]);

    // Second cancel attempt must be rejected (cannot cancel already cancelled order)
    $secondResponse = $this->actingAs($admin)->post('/admin/orders/' . $order->id . '/cancel', [
        'reason' => 'Second attempt to cancel',
    ]);

    $secondResponse->assertSessionHas('error');

    // Stock must not be restored twice
    $variant->refresh();
    expect($variant->stock_quantity)->toBe(10);
});

test('cancel shipped order is rejected', function () {
    $admin = User::factory()->admin()->create();
    [$order, $variant, $shipment] = createTestOrder(['order_status' => 'shipped'], 1, 10);

    $variant->refresh();
    expect($variant->stock_quantity)->toBe(9);

    $response = $this->actingAs($admin)->post('/admin/orders/' . $order->id . '/cancel', [
        'reason' => 'Attempting to cancel shipped package',
    ]);

    $response->assertSessionHas('error');

    $order->refresh();
    $variant->refresh();
    expect($order->order_status)->toBe('shipped')
        ->and($variant->stock_quantity)->toBe(9);
});

test('order cancellation requires minimum 5 characters reason', function () {
    $admin = User::factory()->admin()->create();
    [$order] = createTestOrder(['order_status' => 'confirmed']);

    $response = $this->actingAs($admin)->post('/admin/orders/' . $order->id . '/cancel', [
        'reason' => 'bad',
    ]);

    $response->assertSessionHasErrors('reason');
    $order->refresh();
    expect($order->order_status)->toBe('confirmed');
});

/* =========================================================================
 * 3. PROCESSING & SHIPMENT ADVANCEMENT RULES
 * ========================================================================= */

test('KHQR unpaid order cannot go to processing or shipped; COD can', function () {
    $admin = User::factory()->admin()->create();
    $shipmentService = app(ShipmentService::class);

    // 1. KHQR Unpaid Order
    [$khqrOrder, , $khqrShipment] = createTestOrder([
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);

    // Advance to processing must be rejected
    $resProcessing = $this->actingAs($admin)->put('/admin/orders/' . $khqrOrder->id . '/processing');
    $resProcessing->assertSessionHas('error');
    $khqrOrder->refresh();
    expect($khqrOrder->order_status)->toBe('confirmed');

    // Advance to shipped via ShipmentService must throw exception
    expect(fn() => $shipmentService->updateStatus($khqrShipment, 'shipped'))
        ->toThrow(\InvalidArgumentException::class);

    // 2. COD Order
    [$codOrder, , $codShipment] = createTestOrder([
        'order_status' => 'confirmed',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);

    // Advance to processing must succeed for COD
    $resCodProcessing = $this->actingAs($admin)->put('/admin/orders/' . $codOrder->id . '/processing');
    $resCodProcessing->assertSessionHas('success');
    $codOrder->refresh();
    expect($codOrder->order_status)->toBe('processing');

    // Advance to shipped via ShipmentService must succeed for COD
    $updatedShipment = $shipmentService->updateStatus($codShipment, 'shipped');
    $codOrder->refresh();
    expect($updatedShipment->status)->toBe('shipped')
        ->and($codOrder->order_status)->toBe('shipped');
});

test('KHQR paid order can advance to processing and shipped', function () {
    $admin = User::factory()->admin()->create();
    $shipmentService = app(ShipmentService::class);

    [$paidOrder, , $paidShipment] = createTestOrder([
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
    ]);

    // Advance to processing succeeds
    $res = $this->actingAs($admin)->put('/admin/orders/' . $paidOrder->id . '/processing');
    $res->assertSessionHas('success');
    $paidOrder->refresh();
    expect($paidOrder->order_status)->toBe('processing');

    // Advance to shipped succeeds
    $updatedShipment = $shipmentService->updateStatus($paidShipment, 'shipped');
    $paidOrder->refresh();
    expect($updatedShipment->status)->toBe('shipped')
        ->and($paidOrder->order_status)->toBe('shipped');
});

/* =========================================================================
 * 4. SHIPMENT SERVICE AUDIT & HISTORY TESTS
 * ========================================================================= */

test('ShipmentService writes order_status_history and admin audit log on shipped and delivered', function () {
    $admin = User::factory()->admin()->create();
    $shipmentService = app(ShipmentService::class);

    [$order, , $shipment] = createTestOrder([
        'order_status' => 'processing',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);

    $this->actingAs($admin);

    // Update to shipped
    $shipmentService->updateStatus($shipment, 'shipped');
    $order->refresh();

    expect($order->order_status)->toBe('shipped');

    $this->assertDatabaseHas('order_status_history', [
        'order_id' => $order->id,
        'old_status' => 'processing',
        'new_status' => 'shipped',
    ]);

    $this->assertDatabaseHas('admin_audit_logs', [
        'module' => 'shipments',
        'action' => 'update_status',
        'target_table' => 'orders',
        'target_id' => $order->id,
    ]);

    // Update to delivered
    $shipmentService->updateStatus($shipment, 'delivered');
    $order->refresh();

    expect($order->order_status)->toBe('delivered');

    $this->assertDatabaseHas('order_status_history', [
        'order_id' => $order->id,
        'old_status' => 'shipped',
        'new_status' => 'delivered',
    ]);
});

/* =========================================================================
 * 5. PAYMENTS MODULE: MARK-PAID RULES
 * ========================================================================= */

test('mark-paid rules enforce COD only, order shipped or delivered, not already paid, and order not cancelled', function () {
    $admin = User::factory()->admin()->create();

    // 1. Wrong method: KHQR payment
    [$khqrOrder, , , $khqrPayment] = createTestOrder([
        'order_status' => 'shipped',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);
    $resKhqr = $this->actingAs($admin)->post('/admin/payments/' . $khqrPayment->id . '/mark-paid');
    $resKhqr->assertSessionHas('error');

    // 2. Wrong order status: COD payment on 'confirmed' order (not yet shipped)
    [$unshippedOrder, , , $unshippedPayment] = createTestOrder([
        'order_status' => 'confirmed',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);
    $resUnshipped = $this->actingAs($admin)->post('/admin/payments/' . $unshippedPayment->id . '/mark-paid');
    $resUnshipped->assertSessionHas('error');

    // 3. Cancelled order: COD payment on cancelled order
    [$cancelledOrder, , , $cancelledPayment] = createTestOrder([
        'order_status' => 'cancelled',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);
    $resCancelled = $this->actingAs($admin)->post('/admin/payments/' . $cancelledPayment->id . '/mark-paid');
    $resCancelled->assertSessionHas('error');

    // 4. Already paid payment
    [$paidOrder, , , $alreadyPaidPayment] = createTestOrder([
        'order_status' => 'delivered',
        'payment_method' => 'cod',
        'payment_status' => 'paid',
    ]);
    $alreadyPaidPayment->update(['status' => 'paid']);
    $resAlreadyPaid = $this->actingAs($admin)->post('/admin/payments/' . $alreadyPaidPayment->id . '/mark-paid');
    $resAlreadyPaid->assertSessionHas('error');

    // 5. Valid case: COD, pending, order status 'shipped'
    [$validOrder, , , $validPayment] = createTestOrder([
        'order_status' => 'shipped',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);
    $resValid = $this->actingAs($admin)->post('/admin/payments/' . $validPayment->id . '/mark-paid');
    $resValid->assertSessionHas('success');

    $validPayment->refresh();
    $validOrder->refresh();
    expect($validPayment->status)->toBe('paid')
        ->and($validPayment->paid_at)->not->toBeNull()
        ->and($validOrder->payment_status)->toBe('paid');

    $this->assertDatabaseHas('admin_audit_logs', [
        'module' => 'payments',
        'action' => 'mark_paid',
        'target_table' => 'payments',
        'target_id' => $validPayment->id,
    ]);
});

/* =========================================================================
 * 6. PAYMENTS MODULE: REFUND RULES
 * ========================================================================= */

test('refund rules enforce only paid payments, requires note, writes audit without changing order status or stock', function () {
    $admin = User::factory()->admin()->create();

    // 1. Pending payment cannot be refunded
    [$pendingOrder, , , $pendingPayment] = createTestOrder([
        'order_status' => 'confirmed',
        'payment_status' => 'pending',
    ]);
    $resPending = $this->actingAs($admin)->post('/admin/payments/' . $pendingPayment->id . '/refund', [
        'notes' => 'Customer requested refund before pay',
    ]);
    $resPending->assertSessionHas('error');

    // 2. Note is required and must be at least 5 chars
    [$paidOrder, $variant, , $paidPayment] = createTestOrder([
        'order_status' => 'confirmed',
        'payment_status' => 'paid',
    ], 1, 10);
    $paidPayment->update(['status' => 'paid']);

    $resShortNote = $this->actingAs($admin)->post('/admin/payments/' . $paidPayment->id . '/refund', [
        'notes' => 'bad',
    ]);
    $resShortNote->assertSessionHasErrors('notes');

    // 3. Valid refund
    $initialStock = $variant->fresh()->stock_quantity;

    $resValid = $this->actingAs($admin)->post('/admin/payments/' . $paidPayment->id . '/refund', [
        'notes' => 'Off-platform Wing money transfer refund issued to customer',
    ]);
    $resValid->assertSessionHas('success');

    $paidPayment->refresh();
    $paidOrder->refresh();

    // Payment and Order payment_status become refunded
    expect($paidPayment->status)->toBe('refunded')
        ->and($paidOrder->payment_status)->toBe('refunded');

    // Order status and stock remain unchanged
    expect($paidOrder->order_status)->toBe('confirmed')
        ->and($variant->fresh()->stock_quantity)->toBe($initialStock);

    // Audit logs recorded
    $this->assertDatabaseHas('admin_audit_logs', [
        'module' => 'payments',
        'action' => 'refund',
        'target_table' => 'payments',
        'target_id' => $paidPayment->id,
    ]);

    // Order status history recorded with note
    $this->assertDatabaseHas('order_status_history', [
        'order_id' => $paidOrder->id,
        'notes' => 'Off-platform refund recorded: Off-platform Wing money transfer refund issued to customer',
    ]);
});

/* =========================================================================
 * 7. LIST FILTERS & SEARCH
 * ========================================================================= */

test('orders list filters and search return correct rows', function () {
    $admin = User::factory()->admin()->create();

    // Order A
    [$orderA] = createTestOrder([
        'order_number' => 'HOYOS-ORD-A111',
        'customer_name' => 'Alice Wonder',
        'customer_phone' => '012111111',
        'order_status' => 'confirmed',
        'payment_status' => 'pending',
        'payment_method' => 'aba_khqr',
    ]);

    // Order B
    [$orderB] = createTestOrder([
        'order_number' => 'HOYOS-ORD-B222',
        'customer_name' => 'Bob Marley',
        'customer_phone' => '012222222',
        'order_status' => 'processing',
        'payment_status' => 'paid',
        'payment_method' => 'aba_khqr',
    ]);

    // Order C
    [$orderC] = createTestOrder([
        'order_number' => 'HOYOS-ORD-C333',
        'customer_name' => 'Charlie Chaplin',
        'customer_phone' => '012333333',
        'order_status' => 'shipped',
        'payment_status' => 'paid',
        'payment_method' => 'cod',
    ]);

    // Filter by order_status = processing
    $res = $this->actingAs($admin)->get('/admin/orders?order_status=processing');
    $res->assertSee($orderB->order_number);
    $res->assertDontSee($orderA->order_number);
    $res->assertDontSee($orderC->order_number);

    // Filter by payment_status = pending
    $res = $this->actingAs($admin)->get('/admin/orders?payment_status=pending');
    $res->assertSee($orderA->order_number);
    $res->assertDontSee($orderB->order_number);
    $res->assertDontSee($orderC->order_number);

    // Filter by payment_method = cod
    $res = $this->actingAs($admin)->get('/admin/orders?payment_method=cod');
    $res->assertSee($orderC->order_number);
    $res->assertDontSee($orderA->order_number);
    $res->assertDontSee($orderB->order_number);

    // Search by name "Bob"
    $res = $this->actingAs($admin)->get('/admin/orders?search=Bob');
    $res->assertSee($orderB->order_number);
    $res->assertDontSee($orderA->order_number);
    $res->assertDontSee($orderC->order_number);

    // Search by phone "012333333"
    $res = $this->actingAs($admin)->get('/admin/orders?search=012333333');
    $res->assertSee($orderC->order_number);
    $res->assertDontSee($orderA->order_number);
    $res->assertDontSee($orderB->order_number);
});

test('payments list filters and search return correct rows', function () {
    $admin = User::factory()->admin()->create();

    [$orderA, , , $payA] = createTestOrder([
        'order_number' => 'HOYOS-PAY-A1',
        'customer_name' => 'Darth Vader',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);
    $payA->update(['transaction_id' => 'TXN-VADER-999', 'status' => 'pending']);

    [$orderB, , , $payB] = createTestOrder([
        'order_number' => 'HOYOS-PAY-B2',
        'customer_name' => 'Luke Skywalker',
        'payment_method' => 'cod',
        'payment_status' => 'paid',
    ]);
    $payB->update(['transaction_id' => 'TXN-LUKE-777', 'status' => 'paid']);

    // Filter by method = cod
    $res = $this->actingAs($admin)->get('/admin/payments?method=cod');
    $res->assertSee($payB->transaction_id);
    $res->assertDontSee($payA->transaction_id);

    // Filter by status = pending
    $res = $this->actingAs($admin)->get('/admin/payments?status=pending');
    $res->assertSee($payA->transaction_id);
    $res->assertDontSee($payB->transaction_id);

    // Search by transaction_id
    $res = $this->actingAs($admin)->get('/admin/payments?search=VADER');
    $res->assertSee($payA->transaction_id);
    $res->assertDontSee($payB->transaction_id);
});

test('orders index and show render dark glassmorphism shared components and correct sidebar active state', function () {
    $admin = User::factory()->admin()->create();

    [$order] = createTestOrder([
        'order_number' => 'HOYOS-ORD-GLASS1',
        'customer_name' => 'Glass Tester',
        'order_status' => 'confirmed',
        'payment_status' => 'pending',
    ]);

    // 1. Orders Index Page
    $indexRes = $this->actingAs($admin)->get('/admin/orders');
    $indexRes->assertOk();
    $indexRes->assertSee('Customer Orders');
    $indexRes->assertSee('Fulfillment Pipeline');
    $indexRes->assertSee('Awaiting Processing');
    $indexRes->assertSee('Awaiting Payment');
    $indexRes->assertSee('In Transit / Shipped');
    $indexRes->assertSee('Delivered');
    $indexRes->assertSee('glass-nav-active'); // Sidebar active state on Orders & Confirm
    $indexRes->assertSee('#HOYOS-ORD-GLASS1');
    $indexRes->assertSee('Details');

    // 2. Orders Show Detail Page
    $showRes = $this->actingAs($admin)->get('/admin/orders/' . $order->id);
    $showRes->assertOk();
    $showRes->assertSee('#' . $order->order_number);
    $showRes->assertSee('Customer Snapshot');
    $showRes->assertSee('Delivery Destination');
    $showRes->assertSee('Logistics & Dispatch');
    $showRes->assertSee('Ordered Line Items');
    $showRes->assertSee('Financial Summary');
    $showRes->assertSee('Payments History');
    $showRes->assertSee('Cancel Order');
    $showRes->assertSee('Confirm Cancellation');
    $showRes->assertSee('glass-nav-active');
});
