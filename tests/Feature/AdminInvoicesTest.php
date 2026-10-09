<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;

function createInvoiceTestOrder(array $attributes = [], array $shipmentAttributes = []): Order
{
    $category = Category::firstOrCreate(['slug' => 'tees'], ['name' => 'Tees']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Cyber Matrix Jersey',
        'slug' => 'cyber-matrix-jersey-' . uniqid(),
        'price' => 45.00,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'CMJ-M-' . uniqid(),
        'stock_quantity' => 20,
    ]);

    $order = Order::create(array_merge([
        'order_number' => 'HOYOS-' . strtoupper(uniqid()),
        'customer_name' => 'John Rith',
        'customer_email' => 'john@example.com',
        'customer_phone' => '+85512345678',
        'shipping_address' => 'House 12, St 2004',
        'shipping_city' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 45.00,
        'shipping_fee' => 2.00,
        'total_amount' => 47.00,
        'order_status' => 'confirmed',
        'payment_status' => 'paid',
        'payment_method' => 'aba_khqr',
    ], $attributes));

    OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_size' => 'M',
        'unit_price' => 45.00,
        'quantity' => 1,
        'subtotal' => 45.00,
    ]);

    Payment::create([
        'order_id' => $order->id,
        'payment_method' => $order->payment_method,
        'transaction_id' => 'TX-' . uniqid(),
        'amount' => $order->total_amount,
        'currency' => 'USD',
        'status' => $order->payment_status,
        'paid_at' => now(),
    ]);

    Shipment::create(array_merge([
        'order_id' => $order->id,
        'carrier' => 'J&T Express',
        'tracking_number' => 'JT' . rand(100000, 999999) . 'KH',
        'status' => 'order_confirmed',
    ], $shipmentAttributes));

    return $order;
}

test('guest cannot access admin invoices', function () {
    $order = createInvoiceTestOrder();

    $this->get('/admin/invoices')->assertRedirect('/login');
    $this->get("/admin/invoices/{$order->id}")->assertRedirect('/login');
});

test('regular customer cannot access admin invoices', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $order = createInvoiceTestOrder();

    $this->actingAs($customer)->get('/admin/invoices')->assertForbidden();
    $this->actingAs($customer)->get("/admin/invoices/{$order->id}")->assertForbidden();
});

test('admin can access invoices list and view customer billing entries', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = createInvoiceTestOrder([
        'customer_name' => 'Alice Mara',
        'customer_phone' => '+85511223344',
    ]);

    $response = $this->actingAs($admin)->get('/admin/invoices');

    $response->assertOk();
    $response->assertSee('Customer Sales Invoices & Receipts');
    $response->assertSee('Alice Mara');
    $response->assertSee('+85511223344');
    $response->assertSee($order->order_number);
    $response->assertSee('INV-' . $order->created_at->format('Ymd'));
});

test('admin can search and filter invoices by payment status and carrier', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $paidOrder = createInvoiceTestOrder([
        'customer_name' => 'Phanith Dara',
        'payment_status' => 'paid',
    ], ['carrier' => 'J&T Express']);

    $pendingOrder = createInvoiceTestOrder([
        'customer_name' => 'Sophea Pich',
        'payment_status' => 'pending',
    ], ['carrier' => 'Virak Buntham']);

    // 1. Search by customer name
    $searchResponse = $this->actingAs($admin)->get('/admin/invoices?search=Phanith');
    $searchResponse->assertOk();
    $searchResponse->assertSee('Phanith Dara');
    $searchResponse->assertDontSee('Sophea Pich');

    // 2. Filter by payment status
    $statusResponse = $this->actingAs($admin)->get('/admin/invoices?payment_status=pending');
    $statusResponse->assertOk();
    $statusResponse->assertSee('Sophea Pich');
    $statusResponse->assertDontSee('Phanith Dara');

    // 3. Filter by delivery carrier
    $carrierResponse = $this->actingAs($admin)->get('/admin/invoices?carrier=Virak');
    $carrierResponse->assertOk();
    $carrierResponse->assertSee('Sophea Pich');
    $carrierResponse->assertDontSee('Phanith Dara');
});

test('admin can view single invoice with printable receipt sheet', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = createInvoiceTestOrder([
        'customer_name' => 'Vann Da',
        'total_amount' => 47.00,
    ]);

    $response = $this->actingAs($admin)->get("/admin/invoices/{$order->id}");

    $response->assertOk();
    $response->assertSee('HOYOS');
    $response->assertSee('TAX INVOICE / RECEIPT');
    $response->assertSee('INV-' . $order->created_at->format('Ymd'));
    $response->assertSee('Vann Da');
    $response->assertSee('Cyber Matrix Jersey');
    $response->assertSee('$47.00');
    $response->assertSee('Print Invoice');
});
