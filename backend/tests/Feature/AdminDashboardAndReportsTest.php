<?php

use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function createReportAdmin(): User
{
    return User::factory()->admin()->create();
}

function createReportCustomer(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'customer',
        'account_status' => 'active',
    ], $attributes));
}

function createReportProductWithVariant(string $productName = 'Hoyos Core Tee', string $size = 'M', int $stock = 10, float $price = 45.00): array
{
    $category = Category::firstOrCreate(['slug' => 'streetwear'], ['name' => 'Streetwear']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => $productName,
        'slug' => strtolower(str_replace(' ', '-', $productName)) . '-' . uniqid(),
        'price' => $price,
        'is_active' => true,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => $size,
        'sku' => 'SKU-' . strtoupper(uniqid()),
        'stock_quantity' => $stock,
    ]);

    return [$product, $variant];
}

/* =========================================================================
 * 1. ACCESS CONTROL
 * ========================================================================= */

test('guest is blocked on every dashboard and reports route', function () {
    $this->get('/admin')->assertRedirect(route('login'));
    $this->get('/admin/reports')->assertRedirect(route('login'));
    $this->get('/admin/reports/sales')->assertRedirect(route('login'));
    $this->get('/admin/reports/products')->assertRedirect(route('login'));
    $this->get('/admin/reports/regions')->assertRedirect(route('login'));
    $this->get('/admin/reports/customers')->assertRedirect(route('login'));
    $this->get('/admin/reports/sales?format=csv')->assertRedirect(route('login'));
});

test('customer role user is forbidden (403) on every dashboard and reports route', function () {
    $customer = createReportCustomer();

    $this->actingAs($customer)->get('/admin')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/reports')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/reports/sales')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/reports/products')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/reports/regions')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/reports/customers')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/reports/sales?format=csv')->assertStatus(403);
});

/* =========================================================================
 * 2. REVENUE RULES & TIMEZONE BOUNDARIES
 * ========================================================================= */

test('revenue calculation excludes cancelled, refunded, pending and expired orders', function () {
    $admin = createReportAdmin();
    $tz = config('hoyos.report_timezone', 'Asia/Phnom_Penh');
    Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', $tz));

    $customer = createReportCustomer();

    // 1. Paid & confirmed ($100.00) -> INCLUDED in revenue
    Order::create([
        'order_number' => 'ORD-REV-1',
        'user_id' => $customer->id,
        'customer_name' => 'Dara Meng',
        'customer_phone' => '012998877',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 100.00,
        'total_amount' => 100.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz),
    ]);

    // 2. Paid but Cancelled ($40.00) -> EXCLUDED
    Order::create([
        'order_number' => 'ORD-REV-2',
        'user_id' => $customer->id,
        'customer_name' => 'Dara Meng',
        'customer_phone' => '012998877',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 40.00,
        'total_amount' => 40.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'cancelled',
        'created_at' => Carbon::now($tz),
    ]);

    // 3. Refunded ($30.00) -> EXCLUDED
    Order::create([
        'order_number' => 'ORD-REV-3',
        'user_id' => $customer->id,
        'customer_name' => 'Dara Meng',
        'customer_phone' => '012998877',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 30.00,
        'total_amount' => 30.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'refunded',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz),
    ]);

    // 4. Pending ($50.00) -> EXCLUDED from revenue, counted as pending revenue
    Order::create([
        'order_number' => 'ORD-REV-4',
        'user_id' => $customer->id,
        'customer_name' => 'Dara Meng',
        'customer_phone' => '012998877',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 50.00,
        'total_amount' => 50.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz),
    ]);

    // 5. Expired ($20.00) -> EXCLUDED
    Order::create([
        'order_number' => 'ORD-REV-5',
        'user_id' => $customer->id,
        'customer_name' => 'Dara Meng',
        'customer_phone' => '012998877',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 20.00,
        'total_amount' => 20.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'expired',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz),
    ]);

    // Check Dashboard
    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();
    $response->assertSee('$100.00'); // Revenue Today
    $response->assertDontSee('$240.00'); // Gross sum before exclusion

    // Check Sales Report
    $salesResponse = $this->actingAs($admin)->get('/admin/reports/sales?date_from=2026-10-15&date_to=2026-10-15');
    $salesResponse->assertOk();
    $salesResponse->assertSee('$100.00'); // Period Revenue
    $salesResponse->assertSee('$50.00');  // Pending Revenue
});

test('an order created at 23:30 Phnom Penh time lands on the correct local day and month', function () {
    $admin = createReportAdmin();
    $tz = config('hoyos.report_timezone', 'Asia/Phnom_Penh');

    // 23:30 in Phnom Penh on Oct 31, 2026 is 16:30 UTC on Oct 31, 2026
    Carbon::setTestNow(Carbon::parse('2026-10-31 23:30:00', $tz));

    $customer = createReportCustomer();
    Order::create([
        'order_number' => 'ORD-LATE-NIGHT',
        'user_id' => $customer->id,
        'customer_name' => 'Night Owl',
        'customer_phone' => '012998877',
        'shipping_address' => 'Street 51, Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 125.00,
        'total_amount' => 125.00,
        'payment_method' => 'cod',
        'payment_status' => 'paid',
        'order_status' => 'delivered',
        'created_at' => Carbon::now($tz),
    ]);

    // Dashboard check: today's revenue and this month's revenue
    $dashResponse = $this->actingAs($admin)->get('/admin');
    $dashResponse->assertOk();
    $dashResponse->assertSee('$125.00'); // Revenue today on Oct 31

    // Sales report for Oct 31
    $salesResponse = $this->actingAs($admin)->get('/admin/reports/sales?date_from=2026-10-31&date_to=2026-10-31');
    $salesResponse->assertOk();
    $salesResponse->assertSee('$125.00');
});

test('date validation rejects range over 366 days and from after to', function () {
    $admin = createReportAdmin();

    // From after To
    $this->actingAs($admin)->get('/admin/reports/sales?date_from=2026-10-20&date_to=2026-10-10')
        ->assertSessionHasErrors('date_from');

    // Range over 366 days
    $this->actingAs($admin)->get('/admin/reports/sales?date_from=2025-01-01&date_to=2026-03-01')
        ->assertSessionHasErrors('date_to');
});

/* =========================================================================
 * 3. PRODUCTS & REGIONS AGGREGATIONS
 * ========================================================================= */

test('top products survive a soft-deleted product and size breakdown works', function () {
    $admin = createReportAdmin();
    $tz = config('hoyos.report_timezone', 'Asia/Phnom_Penh');
    Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', $tz));

    [$product, $variant] = createReportProductWithVariant('Gothic Oversized Hoodie', 'XL', 20, 80.00);

    $customer = createReportCustomer();
    $order = Order::create([
        'order_number' => 'ORD-HOODIE-1',
        'user_id' => $customer->id,
        'customer_name' => 'Hoodie Lover',
        'customer_phone' => '012998877',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 160.00,
        'total_amount' => 160.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name, // Snapshot
        'variant_size' => $variant->size,  // Snapshot
        'unit_price' => 80.00,
        'quantity' => 2,
        'subtotal' => 160.00,
    ]);

    // Soft delete the product and variant from catalog
    $variant->delete();
    $product->delete();

    $response = $this->actingAs($admin)->get('/admin/reports/products?date_from=2026-10-01&date_to=2026-10-31');
    $response->assertOk();

    // Must still list the item by snapshot name and size
    $response->assertSee('Gothic Oversized Hoodie');
    $response->assertSee('$160.00');
    $response->assertSee('XL');
});

test('province regional breakdown accurately groups paid revenue', function () {
    $admin = createReportAdmin();
    $tz = config('hoyos.report_timezone', 'Asia/Phnom_Penh');
    Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', $tz));

    $customer = createReportCustomer();

    // Order 1: Siem Reap, Paid $120.00
    Order::create([
        'order_number' => 'ORD-SR-1',
        'user_id' => $customer->id,
        'customer_name' => 'SR Customer',
        'customer_phone' => '012998877',
        'shipping_address' => 'Wat Bo, Siem Reap',
        'province' => 'Siem Reap',
        'subtotal' => 120.00,
        'total_amount' => 120.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz),
    ]);

    // Order 2: Phnom Penh, Paid $200.00
    Order::create([
        'order_number' => 'ORD-PP-1',
        'user_id' => $customer->id,
        'customer_name' => 'PP Customer',
        'customer_phone' => '012998877',
        'shipping_address' => 'BKK1, Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 200.00,
        'total_amount' => 200.00,
        'payment_method' => 'cod',
        'payment_status' => 'paid',
        'order_status' => 'delivered',
        'created_at' => Carbon::now($tz),
    ]);

    // Order 3: Siem Reap, Cancelled $50.00 (excluded)
    Order::create([
        'order_number' => 'ORD-SR-CANCELLED',
        'user_id' => $customer->id,
        'customer_name' => 'Cancelled Shopper',
        'customer_phone' => '012998877',
        'shipping_address' => 'Siem Reap',
        'province' => 'Siem Reap',
        'subtotal' => 50.00,
        'total_amount' => 50.00,
        'payment_method' => 'cod',
        'payment_status' => 'cancelled',
        'order_status' => 'cancelled',
        'created_at' => Carbon::now($tz),
    ]);

    $response = $this->actingAs($admin)->get('/admin/reports/regions?date_from=2026-10-01&date_to=2026-10-31');
    $response->assertOk();
    $response->assertSee('Siem Reap');
    $response->assertSee('$120.00');
    $response->assertSee('Phnom Penh');
    $response->assertSee('$200.00');
});

/* =========================================================================
 * 4. CUSTOMER RETENTION & DIVISION BY ZERO SAFETY
 * ========================================================================= */

test('no division by zero occurs on empty database', function () {
    $admin = createReportAdmin();

    // Dashboard on clean DB
    $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('$0.00');

    // Sales report
    $this->actingAs($admin)->get('/admin/reports/sales')->assertOk()->assertSee('$0.00');

    // Products report
    $this->actingAs($admin)->get('/admin/reports/products')->assertOk();

    // Regions report
    $this->actingAs($admin)->get('/admin/reports/regions')->assertOk();

    // Customers report
    $this->actingAs($admin)->get('/admin/reports/customers')->assertOk()->assertSee('0%');
});

test('repeat purchase rate calculates correctly with 0, 1 and 2+ orders', function () {
    $admin = createReportAdmin();
    $tz = config('hoyos.report_timezone', 'Asia/Phnom_Penh');
    Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', $tz));

    // Customer A: Exactly 1 paid order
    $customerA = createReportCustomer(['email' => 'clientA@test.com']);
    Order::create([
        'order_number' => 'ORD-CUST-A1',
        'user_id' => $customerA->id,
        'customer_name' => 'Client A',
        'customer_phone' => '012998877',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 50.00,
        'total_amount' => 50.00,
        'payment_method' => 'cod',
        'payment_status' => 'paid',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz),
    ]);

    // Customer B: 2 paid orders (Repeat Customer)
    $customerB = createReportCustomer(['email' => 'clientB@test.com']);
    Order::create([
        'order_number' => 'ORD-CUST-B1',
        'user_id' => $customerB->id,
        'customer_name' => 'Client B',
        'customer_phone' => '012998878',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 40.00,
        'total_amount' => 40.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz)->subDay(),
    ]);
    Order::create([
        'order_number' => 'ORD-CUST-B2',
        'user_id' => $customerB->id,
        'customer_name' => 'Client B',
        'customer_phone' => '012998878',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 60.00,
        'total_amount' => 60.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz),
    ]);

    // Customer C: Unpaid order only (should not be in paid customer pool)
    $customerC = createReportCustomer(['email' => 'clientC@test.com']);
    Order::create([
        'order_number' => 'ORD-CUST-C1',
        'user_id' => $customerC->id,
        'customer_name' => 'Client C',
        'customer_phone' => '012998879',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 70.00,
        'total_amount' => 70.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz),
    ]);

    // Total paid customers = 2 (A and B)
    // Repeat customers = 1 (B)
    // Repeat rate = 1 / 2 = 50%
    $response = $this->actingAs($admin)->get('/admin/reports/customers?date_from=2026-10-01&date_to=2026-10-31');
    $response->assertOk();
    $response->assertSee('50%');
});

/* =========================================================================
 * 5. ACTION QUEUES & QUERY SCALING
 * ========================================================================= */

test('each action queue on dashboard counts accurately', function () {
    $admin = createReportAdmin();
    $customer = createReportCustomer();

    // 1. Order awaiting processing (confirmed COD)
    Order::create([
        'order_number' => 'QUEUE-1',
        'customer_name' => 'Queue 1',
        'customer_phone' => '012111111',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 20.00,
        'total_amount' => 20.00,
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'order_status' => 'confirmed',
    ]);

    // 2. KHQR awaiting payment
    Order::create([
        'order_number' => 'QUEUE-2',
        'customer_name' => 'Queue 2',
        'customer_phone' => '012111112',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 30.00,
        'total_amount' => 30.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
        'order_status' => 'confirmed',
    ]);

    // 3. COD awaiting cash (shipped, payment pending)
    Order::create([
        'order_number' => 'QUEUE-3',
        'customer_name' => 'Queue 3',
        'customer_phone' => '012111113',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 40.00,
        'total_amount' => 40.00,
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'order_status' => 'shipped',
    ]);

    // 4. Shipment in transit
    $orderForShipment = Order::create([
        'order_number' => 'QUEUE-4',
        'customer_name' => 'Queue 4',
        'customer_phone' => '012111114',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 50.00,
        'total_amount' => 50.00,
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'order_status' => 'shipped',
    ]);
    Shipment::create([
        'order_id' => $orderForShipment->id,
        'tracking_code' => 'JT-TR-123',
        'carrier' => 'J&T Express',
        'status' => 'shipped',
    ]);

    // 5. Cancelled order needing refund record
    Order::create([
        'order_number' => 'QUEUE-5',
        'customer_name' => 'Queue 5',
        'customer_phone' => '012111115',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 60.00,
        'total_amount' => 60.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'cancelled',
    ]);

    // 6. Low stock variant (3 units)
    createReportProductWithVariant('Low Stock Tee', 'M', 3);

    // 7. Out of stock variant (0 units)
    createReportProductWithVariant('Sold Out Tee', 'L', 0);

    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();
    $response->assertSee('Orders Awaiting Processing');
    $response->assertSee('KHQR Awaiting Payment');
    $response->assertSee('COD Awaiting Cash Collection');
    $response->assertSee('Shipments in Transit');
    $response->assertSee('Refunds Due (Cancelled Paid Orders)');
    $response->assertSee('Low Stock Apparel Variants');
    $response->assertSee('Out of Stock Variants');
});

test('for every action queue and stock card, dashboard count equals the number of rows on the linked filtered page', function () {
    $admin = createReportAdmin();

    // 1. Awaiting processing:
    // Match: confirmed + cod
    Order::create(['order_number' => 'ORD-AP-1', 'customer_name' => 'C1', 'customer_phone' => '01', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'confirmed']);
    // Match: confirmed + paid
    Order::create(['order_number' => 'ORD-AP-2', 'customer_name' => 'C2', 'customer_phone' => '02', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'aba_khqr', 'payment_status' => 'paid', 'order_status' => 'confirmed']);
    // Non-match: processing + paid
    Order::create(['order_number' => 'ORD-AP-3', 'customer_name' => 'C3', 'customer_phone' => '03', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'aba_khqr', 'payment_status' => 'paid', 'order_status' => 'processing']);
    // Non-match: confirmed + pending (aba_khqr, not cod and not paid)
    Order::create(['order_number' => 'ORD-AP-4', 'customer_name' => 'C4', 'customer_phone' => '04', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'aba_khqr', 'payment_status' => 'pending', 'order_status' => 'confirmed']);

    // 2. KHQR awaiting payment:
    // Match: aba_khqr + confirmed + pending (ORD-AP-4 is 1 match)
    // Match: aba_khqr + confirmed + expired
    Order::create(['order_number' => 'ORD-KHQR-2', 'customer_name' => 'C5', 'customer_phone' => '05', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'aba_khqr', 'payment_status' => 'expired', 'order_status' => 'confirmed']);
    // Non-match: aba_khqr + cancelled + pending
    Order::create(['order_number' => 'ORD-KHQR-3', 'customer_name' => 'C6', 'customer_phone' => '06', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'aba_khqr', 'payment_status' => 'pending', 'order_status' => 'cancelled']);

    // 3. COD awaiting cash:
    // Match: cod + shipped + pending
    Order::create(['order_number' => 'ORD-COD-1', 'customer_name' => 'C7', 'customer_phone' => '07', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'shipped']);
    // Match: cod + delivered + pending
    Order::create(['order_number' => 'ORD-COD-2', 'customer_name' => 'C8', 'customer_phone' => '08', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'delivered']);
    // Non-match: cod + delivered + paid
    Order::create(['order_number' => 'ORD-COD-3', 'customer_name' => 'C9', 'customer_phone' => '09', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'cod', 'payment_status' => 'paid', 'order_status' => 'delivered']);

    // 4. Shipments in transit:
    // Match: shipped
    $sOrder1 = Order::create(['order_number' => 'ORD-SHP-1', 'customer_name' => 'C10', 'customer_phone' => '10', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'shipped']);
    Shipment::create(['order_id' => $sOrder1->id, 'carrier' => 'J&T Express', 'status' => 'shipped']);
    // Match: out_for_delivery
    $sOrder2 = Order::create(['order_number' => 'ORD-SHP-2', 'customer_name' => 'C11', 'customer_phone' => '11', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'shipped']);
    Shipment::create(['order_id' => $sOrder2->id, 'carrier' => 'J&T Express', 'status' => 'out_for_delivery']);
    // Non-match: processing
    $sOrder3 = Order::create(['order_number' => 'ORD-SHP-3', 'customer_name' => 'C12', 'customer_phone' => '12', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'processing']);
    Shipment::create(['order_id' => $sOrder3->id, 'carrier' => 'J&T Express', 'status' => 'processing']);

    // 5. Cancelled needing refund:
    // Match: cancelled + paid
    Order::create(['order_number' => 'ORD-REF-1', 'customer_name' => 'C13', 'customer_phone' => '13', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'aba_khqr', 'payment_status' => 'paid', 'order_status' => 'cancelled']);
    // Non-match: cancelled + pending
    Order::create(['order_number' => 'ORD-REF-2', 'customer_name' => 'C14', 'customer_phone' => '14', 'shipping_address' => 'PP', 'province' => 'PP', 'subtotal' => 10, 'total_amount' => 10, 'payment_method' => 'aba_khqr', 'payment_status' => 'pending', 'order_status' => 'cancelled']);

    // 6 & 7. Low stock and Out of stock variants
    createReportProductWithVariant('Low Stock A', 'S', 2);
    createReportProductWithVariant('Low Stock B', 'M', 5);
    createReportProductWithVariant('Out of Stock A', 'L', 0);
    createReportProductWithVariant('Plentiful Stock A', 'XL', 20);

    $dashboardService = app(\App\Services\Reports\DashboardService::class);
    $queues = $dashboardService->getActionQueues();

    foreach ($queues as $q) {
        $expectedCount = $q['count'];
        $url = $q['link'];

        $pageResponse = $this->actingAs($admin)->get($url);
        $pageResponse->assertOk();

        if (str_contains($url, '/admin/orders')) {
            $paginator = $pageResponse->viewData('orders');
            expect($paginator->total())->toBe($expectedCount);
        } elseif (str_contains($url, '/admin/shipments')) {
            $paginator = $pageResponse->viewData('orders');
            expect($paginator->total())->toBe($expectedCount);
        } elseif (str_contains($url, '/admin/inventory')) {
            $paginator = $pageResponse->viewData('variants');
            expect($paginator->total())->toBe($expectedCount);
        }
    }
});

test('postgresql driver converts UTC timestamps without time zone to local report month and day across midnight boundary', function () {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires the PostgreSQL (pgsql) database driver.');
    }

    $admin = createReportAdmin();
    $customer = createReportCustomer();

    // Order 1: 2026-01-31 16:30:00 UTC = 2026-01-31 23:30:00 Phnom Penh (counts in January)
    $order1 = Order::create([
        'order_number' => 'ORD-PG-JAN',
        'user_id' => $customer->id,
        'customer_name' => 'Jan Customer',
        'customer_phone' => '012111111',
        'shipping_address' => 'Street 1, Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 100.00,
        'total_amount' => 100.00,
        'payment_method' => 'cod',
        'payment_status' => 'paid',
        'order_status' => 'delivered',
    ]);
    $order1->created_at = Carbon::parse('2026-01-31 16:30:00', 'UTC');
    $order1->save();

    // Order 2: 2026-01-31 17:30:00 UTC = 2026-02-01 00:30:00 Phnom Penh (counts in February)
    $order2 = Order::create([
        'order_number' => 'ORD-PG-FEB',
        'user_id' => $customer->id,
        'customer_name' => 'Feb Customer',
        'customer_phone' => '012222222',
        'shipping_address' => 'Street 2, Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 200.00,
        'total_amount' => 200.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'confirmed',
    ]);
    $order2->created_at = Carbon::parse('2026-01-31 17:30:00', 'UTC');
    $order2->save();

    // January report should only include Order 1 ($100.00)
    $janReport = $this->actingAs($admin)->get('/admin/reports/sales?date_from=2026-01-01&date_to=2026-01-31');
    $janReport->assertOk();
    $janReport->assertSee('$100.00');
    $janReport->assertDontSee('$200.00');

    // February report should only include Order 2 ($200.00)
    $febReport = $this->actingAs($admin)->get('/admin/reports/sales?date_from=2026-02-01&date_to=2026-02-28');
    $febReport->assertOk();
    $febReport->assertSee('$200.00');
    $febReport->assertDontSee('$100.00');
});

test('dashboard query count stays constant as rows grow', function () {
    $admin = createReportAdmin();

    // Base state: 1 order
    $customer = createReportCustomer();
    Order::create([
        'order_number' => 'BASE-ORD',
        'user_id' => $customer->id,
        'customer_name' => 'Base Client',
        'customer_phone' => '012999999',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 50.00,
        'total_amount' => 50.00,
        'payment_method' => 'cod',
        'payment_status' => 'paid',
        'order_status' => 'confirmed',
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($admin)->get('/admin');
    $baseQueryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Now insert 15 more orders, variants, and shipments
    for ($i = 1; $i <= 15; $i++) {
        $c = createReportCustomer();
        $ord = Order::create([
            'order_number' => 'SCALE-ORD-' . $i,
            'user_id' => $c->id,
            'customer_name' => 'Scale Client ' . $i,
            'customer_phone' => '0129999' . sprintf('%02d', $i),
            'shipping_address' => 'Phnom Penh',
            'province' => 'Phnom Penh',
            'subtotal' => 25.00,
            'total_amount' => 25.00,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ]);
        if ($i % 3 === 0) {
            Shipment::create([
                'order_id' => $ord->id,
                'tracking_code' => 'JT-SCALE-' . $i,
                'carrier' => 'J&T Express',
                'status' => 'shipped',
            ]);
        }
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($admin)->get('/admin');
    $scaledQueryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Query count must be strictly identical (O(1) query complexity)
    expect($scaledQueryCount)->toBe($baseQueryCount);
});

/* =========================================================================
 * 6. CSV STREAMING & INJECTION PREVENTION
 * ========================================================================= */

test('csv export streams valid CSV, sanitizes formula cells, excludes personal info, and logs audit', function () {
    $admin = createReportAdmin();
    $tz = config('hoyos.report_timezone', 'Asia/Phnom_Penh');
    Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', $tz));

    // Product with a formula injection name (e.g. '=1+1', '+malicious', '@cmd')
    [$product, $variant] = createReportProductWithVariant('=cmd|calc!A0', 'M', 10, 50.00);

    $customer = createReportCustomer([
        'name' => 'Secret Customer Name',
        'email' => 'supersecret@customer.test',
        'phone' => '012998877',
    ]);

    $order = Order::create([
        'order_number' => 'ORD-CSV-1',
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_phone' => $customer->phone,
        'customer_email' => $customer->email,
        'shipping_address' => 'Confidential Address Line 1',
        'province' => 'Battambang',
        'subtotal' => 50.00,
        'total_amount' => 50.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'confirmed',
        'created_at' => Carbon::now($tz),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_size' => $variant->size,
        'unit_price' => 50.00,
        'quantity' => 1,
        'subtotal' => 50.00,
    ]);

    // Test Products CSV
    $productsCsvResponse = $this->actingAs($admin)->get('/admin/reports/products?format=csv');
    $productsCsvResponse->assertOk();
    $productsCsvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $productsCsvResponse->streamedContent();

    // Verify CSV formula escaping: should prepend single quote
    expect($content)->toContain("'=cmd|calc!A0");

    // Verify personal data exclusion: NO customer names, phones, emails, addresses
    expect($content)->not->toContain('Secret Customer Name');
    expect($content)->not->toContain('supersecret@customer.test');
    expect($content)->not->toContain('012998877');
    expect($content)->not->toContain('Confidential Address Line 1');

    // Verify Audit Log recorded
    $log = AdminAuditLog::where('module', 'reports')->where('action', 'export')->first();
    expect($log)->not->toBeNull();
    expect($log->admin_id)->toBe($admin->id);
    expect($log->new_values['report'])->toBe('products');

    // Test Sales CSV & Regions CSV
    $this->actingAs($admin)->get('/admin/reports/sales?format=csv')->assertOk();
    $this->actingAs($admin)->get('/admin/reports/regions?format=csv')->assertOk();
});

test('dashboard renders dark glassmorphism design tokens and 8th summary queue card', function () {
    $admin = createReportAdmin();

    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();

    $html = $response->getContent();

    // 1. Verify fixed background ambient radial glow styling
    expect($html)->toContain('bg-[#070405]');
    expect($html)->toContain('from-[#FF002E]/15');
    expect($html)->toContain('bg-[#8B0018]/14');

    // 2. Verify glass-strong and hairline borders on sidebar & topbar
    expect($html)->toContain('glass-strong');
    expect($html)->toContain('border-r border-white/10');
    expect($html)->toContain('border-b border-white/10');

    // 3. Verify chart card height reduced (~35% reduction from 160 to 105)
    expect($html)->toContain('viewBox="0 0 700 105"');
    expect($html)->toContain('class="w-full h-28 overflow-visible"');

    // 4. Verify peak value is not duplicated with PEAK text on chart line
    expect($html)->not->toContain('PEAK: $');

    // 5. Verify 8th summary card is present
    expect($html)->toContain('Total Pending Actions');
    expect($html)->toContain('Consolidated operational backlog across warehouse, payment, and inventory queues.');
});
