<?php

use App\Actions\CancelOrderAction;
use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use App\Services\Reports\DashboardService;
use App\Services\ShipmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function phase1Admin(): User
{
    return User::factory()->admin()->create();
}

function phase1Customer(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'customer',
        'account_status' => 'active',
    ], $attrs));
}

function phase1CreateProduct(string $name = 'Phase1 Product', float $price = 50.00): Product
{
    $category = Category::firstOrCreate(['slug' => 'p1-tops'], ['name' => 'P1 Tops']);
    return Product::create([
        'category_id' => $category->id,
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . uniqid(),
        'price' => $price,
        'is_active' => true,
    ]);
}

function phase1CreateOrder(array $attrs = []): Order
{
    $product = phase1CreateProduct();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'P1-M-' . uniqid(),
        'stock_quantity' => 20,
    ]);

    $order = Order::create(array_merge([
        'order_number' => 'HOYOS-P1-' . strtoupper(uniqid()),
        'customer_name' => 'Phase1 Tester',
        'customer_phone' => '012888999',
        'customer_email' => 'p1@hoyos.test',
        'shipping_address' => 'Street 200, Daun Penh',
        'province' => 'Phnom Penh',
        'subtotal' => $product->price,
        'shipping_fee' => 0.00,
        'total_amount' => $product->price,
        'order_status' => 'confirmed',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ], $attrs));

    OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_size' => $variant->size,
        'unit_price' => $product->price,
        'quantity' => 1,
        'subtotal' => $product->price,
    ]);

    return $order;
}

function phase1FakePng(string $filename = 'test.png'): UploadedFile
{
    $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    return UploadedFile::fake()->createWithContent($filename, $content, 'image/png');
}

/* =========================================================================
 * 1a. Shipment tracking and status audit logs
 * ========================================================================= */

test('1a: ShipmentController logs tracking change, ShipmentService logs status change only when changed, no log if neither changed', function () {
    $admin = phase1Admin();
    $order = phase1CreateOrder();

    // 1. Initial shipment has tracking null, status order_confirmed
    $initialLogsCount = AdminAuditLog::count();

    // Update tracking number only: status stays order_confirmed
    $res1 = $this->actingAs($admin)->put("/admin/shipments/{$order->id}", [
        'tracking_number' => 'JT-P1-001',
        'status' => 'order_confirmed',
    ]);
    $res1->assertSessionHas('success');

    // Exactly 1 audit log created (tracking number changed, status did not change)
    $logsAfter1 = AdminAuditLog::where('target_table', 'shipments')->where('target_id', $order->shipment->id)->get();
    expect($logsAfter1)->toHaveCount(1);
    expect($logsAfter1->first()->action)->toBe('update_tracking');
    expect($logsAfter1->first()->old_values)->toBe(['tracking_number' => null]);
    expect($logsAfter1->first()->new_values)->toBe(['tracking_number' => 'JT-P1-001']);

    // 2. Update status only: tracking stays JT-P1-001, status changes to processing
    $res2 = $this->actingAs($admin)->put("/admin/shipments/{$order->id}", [
        'tracking_number' => 'JT-P1-001',
        'status' => 'processing',
    ]);
    $res2->assertSessionHas('success');

    // Exactly 1 new audit log created (status changed, tracking did not change)
    $logsAfter2 = AdminAuditLog::where('target_table', 'shipments')->where('target_id', $order->shipment->id)->get();
    expect($logsAfter2)->toHaveCount(2);
    $statusLog = $logsAfter2->where('action', 'update_status')->first();
    expect($statusLog)->not->toBeNull();
    expect($statusLog->old_values)->toBe(['status' => 'order_confirmed']);
    expect($statusLog->new_values)->toBe(['status' => 'processing']);

    // 3. Neither changed: same tracking number and same status submitted
    $res3 = $this->actingAs($admin)->put("/admin/shipments/{$order->id}", [
        'tracking_number' => 'JT-P1-001',
        'status' => 'processing',
    ]);
    $res3->assertSessionHas('success');

    // Total audit logs remain unchanged!
    $logsAfter3 = AdminAuditLog::where('target_table', 'shipments')->where('target_id', $order->shipment->id)->get();
    expect($logsAfter3)->toHaveCount(2);
});

/* =========================================================================
 * 1b. Restore captures deleted_at BEFORE restore()
 * ========================================================================= */

test('1b: ProductController and ProductVariantController restore capture deleted_at before restore', function () {
    $admin = phase1Admin();
    $product = phase1CreateProduct('Coat to Restore', 120.00);
    $variantM = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'RESTORE-M-' . uniqid(),
        'stock_quantity' => 10,
    ]);
    $variantL = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'L',
        'sku' => 'RESTORE-L-' . uniqid(),
        'stock_quantity' => 15,
    ]);

    // Soft-delete product
    $this->actingAs($admin)->delete("/admin/products/{$product->id}");
    $deletedAtProduct = $product->fresh()->deleted_at;
    expect($deletedAtProduct)->not->toBeNull();

    // Restore product
    $this->actingAs($admin)->post("/admin/products/{$product->id}/restore");
    expect($product->fresh()->trashed())->toBeFalse();

    $productRestoreLog = AdminAuditLog::where('target_table', 'products')
        ->where('target_id', $product->id)
        ->where('action', 'restore')
        ->first();

    expect($productRestoreLog)->not->toBeNull();
    expect($productRestoreLog->old_values['deleted_at'])->not->toBeNull();
    expect($productRestoreLog->old_values['deleted_at'])->toBe($deletedAtProduct->toIso8601String());
    expect($productRestoreLog->new_values['deleted_at'])->toBeNull();

    // Soft-delete variant L (product has variant M so variant L is not the only size)
    $this->actingAs($admin)->delete("/admin/variants/{$variantL->id}");
    $deletedAtVariant = $variantL->fresh()->deleted_at;
    expect($deletedAtVariant)->not->toBeNull();

    // Restore variant L
    $this->actingAs($admin)->post("/admin/variants/{$variantL->id}/restore");
    expect($variantL->fresh()->trashed())->toBeFalse();

    $variantRestoreLog = AdminAuditLog::where('target_table', 'product_variants')
        ->where('target_id', $variantL->id)
        ->where('action', 'restore')
        ->first();

    expect($variantRestoreLog)->not->toBeNull();
    expect($variantRestoreLog->old_values['deleted_at'])->not->toBeNull();
    expect($variantRestoreLog->old_values['deleted_at'])->toBe($deletedAtVariant->toIso8601String());
    expect($variantRestoreLog->new_values['deleted_at'])->toBeNull();
});

/* =========================================================================
 * 1c. Product update audit stores only changed fields
 * ========================================================================= */

test('1c: Product update audit stores only changed fields in both old_values and new_values', function () {
    $admin = phase1Admin();
    $product = phase1CreateProduct('Vintage Tee', 30.00);

    // Update only price from 30.00 to 45.00
    $this->actingAs($admin)->put("/admin/products/{$product->id}", [
        'name' => $product->name,
        'slug' => $product->slug,
        'category_id' => $product->category_id,
        'price' => 45.00,
        'description' => $product->description,
        'is_active' => true,
    ]);

    $updateLog = AdminAuditLog::where('target_table', 'products')
        ->where('target_id', $product->id)
        ->where('action', 'update')
        ->latest('id')
        ->first();

    expect($updateLog)->not->toBeNull();
    // old_values should only contain the changed attribute(s), not the entire model
    expect(array_keys($updateLog->old_values))->toEqual(['price']);
    expect(in_array('name', array_keys($updateLog->old_values)))->toBeFalse();
    expect(in_array('slug', array_keys($updateLog->old_values)))->toBeFalse();
    expect(in_array('category_id', array_keys($updateLog->old_values)))->toBeFalse();

    // new_values contains changed attribute(s) (+ optional note for price)
    expect(array_key_exists('price', $updateLog->new_values))->toBeTrue();
    expect(array_key_exists('name', $updateLog->new_values))->toBeFalse();
    expect(array_key_exists('slug', $updateLog->new_values))->toBeFalse();
});

/* =========================================================================
 * 1d. Product images: log demoted or promoted primary image
 * ========================================================================= */

test('1d: Product images log demoted and promoted primary image on upload, setPrimary, and destroy', function () {
    Storage::fake('public');
    $admin = phase1Admin();
    $product = phase1CreateProduct('Photo Hoodie', 70.00);

    // 1. Upload first image (becomes primary automatically)
    $file1 = phase1FakePng('img1.png');
    $this->actingAs($admin)->post("/admin/products/{$product->id}/images", [
        'images' => [$file1],
    ]);
    $img1 = $product->fresh()->images()->first();
    expect($img1->is_primary)->toBeTrue();

    // 2. Upload second image with is_primary = true -> demotes image 1
    $file2 = phase1FakePng('img2.png');
    $this->actingAs($admin)->post("/admin/products/{$product->id}/images", [
        'images' => [$file2],
        'is_primary' => '1',
    ]);
    $img2 = $product->fresh()->images()->where('id', '!=', $img1->id)->first();
    expect($img2->is_primary)->toBeTrue();
    expect($img1->fresh()->is_primary)->toBeFalse();

    // Check demotion audit log for image 1
    $demoteLog1 = AdminAuditLog::where('target_table', 'product_images')
        ->where('target_id', $img1->id)
        ->where('action', 'demote_primary')
        ->first();
    expect($demoteLog1)->not->toBeNull();
    expect($demoteLog1->old_values)->toBe(['is_primary' => true]);
    expect($demoteLog1->new_values)->toBe(['is_primary' => false]);

    // 3. Set image 1 as primary again via setPrimary -> demotes image 2
    $this->actingAs($admin)->post("/admin/images/{$img1->id}/set-primary");
    expect($img1->fresh()->is_primary)->toBeTrue();
    expect($img2->fresh()->is_primary)->toBeFalse();

    $demoteLog2 = AdminAuditLog::where('target_table', 'product_images')
        ->where('target_id', $img2->id)
        ->where('action', 'demote_primary')
        ->first();
    expect($demoteLog2)->not->toBeNull();

    $setPrimaryLog = AdminAuditLog::where('target_table', 'product_images')
        ->where('target_id', $img1->id)
        ->where('action', 'set_primary')
        ->first();
    expect($setPrimaryLog)->not->toBeNull();

    // 4. Delete image 1 (which is primary) -> promotes next image (image 2)
    $this->actingAs($admin)->delete("/admin/images/{$img1->id}");
    expect($img2->fresh()->is_primary)->toBeTrue();

    $promoteLog = AdminAuditLog::where('target_table', 'product_images')
        ->where('target_id', $img2->id)
        ->where('action', 'promote_primary')
        ->first();
    expect($promoteLog)->not->toBeNull();
    expect($promoteLog->old_values)->toBe(['is_primary' => false]);
    expect($promoteLog->new_values)->toBe(['is_primary' => true]);
});

/* =========================================================================
 * 1e. System cancellations write audit row with null actor
 * ========================================================================= */

test('1e: System cancellations write audit row with null actor and show System in audit UI', function () {
    $admin = phase1Admin();
    $order = phase1CreateOrder([
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);

    // Cancel order through action with null admin (automated system cancellation)
    app(CancelOrderAction::class)->execute($order, admin: null, reason: 'Payment expired after grace period');

    // Assert audit log row exists with admin_id null
    $auditLog = AdminAuditLog::where('target_table', 'orders')
        ->where('target_id', $order->id)
        ->where('action', 'cancel')
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->admin_id)->toBeNull();
    expect($auditLog->old_values['order_status'])->toBe('confirmed');
    expect($auditLog->new_values['order_status'])->toBe('cancelled');

    // Audit logs UI displays "System"
    $response = $this->actingAs($admin)->get('/admin/audit-logs?admin_id=system');
    $response->assertOk();
    $response->assertSee('System');
});

/* =========================================================================
 * 1f. Customer suspend purges active database session rows in same transaction
 * ========================================================================= */

test('1f: Customer suspend deletes database session rows in same transaction', function () {
    config(['session.driver' => 'database']);

    $admin = phase1Admin();
    $customer = phase1Customer();

    // Insert active session for this customer
    $sessionId = 'test_session_' . uniqid();
    DB::table('sessions')->insert([
        'id' => $sessionId,
        'user_id' => $customer->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit/TestBrowser',
        'payload' => base64_encode(serialize(['foo' => 'bar'])),
        'last_activity' => time(),
    ]);

    expect(DB::table('sessions')->where('user_id', $customer->id)->count())->toBe(1);

    // Suspend customer
    $res = $this->actingAs($admin)->post("/admin/customers/{$customer->id}/suspend", [
        'reason' => 'Suspected account compromise',
    ]);
    $res->assertSessionHas('success');

    expect($customer->fresh()->account_status)->toBe('suspended');
    expect(DB::table('sessions')->where('user_id', $customer->id)->count())->toBe(0);
});

/* =========================================================================
 * 1g. Shared fulfillment scope
 * ========================================================================= */

test('1g: Dispatch queue and dashboard awaiting processing share readyForFulfillment scope', function () {
    $admin = phase1Admin();

    // Order 1: COD confirmed -> ready for fulfillment
    $orderCod = phase1CreateOrder([
        'order_status' => 'confirmed',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);

    // Order 2: ABA KHQR paid confirmed -> ready for fulfillment
    $orderPaid = phase1CreateOrder([
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
    ]);

    // Order 3: ABA KHQR unpaid confirmed -> NOT ready for fulfillment
    $orderUnpaid = phase1CreateOrder([
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);

    // Check scope directly
    $readyOrderIds = Order::readyForFulfillment()->pluck('id')->all();
    expect($readyOrderIds)->toContain($orderCod->id);
    expect($readyOrderIds)->toContain($orderPaid->id);
    expect($readyOrderIds)->not->toContain($orderUnpaid->id);

    // Check DashboardService count
    $dashboardService = app(DashboardService::class);
    $queues = $dashboardService->getActionQueues();
    $expectedAwaitingProcessing = Order::where('order_status', 'confirmed')->readyForFulfillment()->count();
    $awaitingQueue = collect($queues)->firstWhere('title', 'Orders Awaiting Processing');
    expect($awaitingQueue)->not->toBeNull();
    expect($awaitingQueue['count'])->toBe($expectedAwaitingProcessing);

    // Check /admin/shipments queue view
    $res = $this->actingAs($admin)->get('/admin/shipments');
    $res->assertOk();
    $res->assertSee($orderCod->order_number);
    $res->assertSee($orderPaid->order_number);
    $res->assertDontSee($orderUnpaid->order_number);
});

/* =========================================================================
 * 1h. Product Edit page N+1 query elimination
 * ========================================================================= */

test('1h: Product Edit page eliminates N+1 query on variants loop using setRelation', function () {
    $admin = phase1Admin();
    $product = phase1CreateProduct('Tactical Cargo Pants', 85.00);

    for ($i = 0; $i < 6; $i++) {
        ProductVariant::create([
            'product_id' => $product->id,
            'size' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'][$i],
            'sku' => "TCP-SIZE-{$i}-" . uniqid(),
            'stock_quantity' => 10,
            'price_adjustment' => 0.00,
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($admin)->get("/admin/products/{$product->id}/edit");
    $response->assertOk();

    $queries = DB::getQueryLog();
    $productQueries = array_filter($queries, function ($q) use ($product) {
        return str_contains($q['query'], 'from "products"') 
            && (str_contains($q['query'], 'where "id" =') || str_contains($q['query'], 'where "products"."id" ='));
    });

    // Exactly 1 product query (the main findOrFail), zero duplicate queries from the 6 variants!
    expect(count($productQueries))->toBe(1);
});

/* =========================================================================
 * PHASE 1.5 TESTS
 * ========================================================================= */

test('1.5a: suspend rotates remember_token and modal copy reflects session driver', function () {
    $admin = phase1Admin();
    $customer = phase1Customer(['remember_token' => 'old_remember_token_12345']);

    $this->actingAs($admin)->post("/admin/customers/{$customer->id}/suspend", [
        'reason' => 'Rotating token test',
    ]);

    $freshCustomer = $customer->fresh();
    expect($freshCustomer->account_status)->toBe('suspended');
    expect($freshCustomer->remember_token)->not->toBe('old_remember_token_12345');
    expect($freshCustomer->remember_token)->not->toBeNull();

    // Check modal copy when driver is database
    config(['session.driver' => 'database']);
    $resDb = $this->actingAs($admin)->get("/admin/customers/{$customer->id}");
    $resDb->assertOk();
    $resDb->assertSee('terminate active sessions');

    // Check modal copy when driver is NOT database
    config(['session.driver' => 'cookie']);
    $resCookie = $this->actingAs($admin)->get("/admin/customers/{$customer->id}");
    $resCookie->assertOk();
    $resCookie->assertSee('blocked on their next request');
});

test('1.5b: shipment-cancel audit handles orders with and without shipment inside same transaction', function () {
    $admin = phase1Admin();

    // 1. Order WITH shipment
    $orderWithShipment = phase1CreateOrder();
    $shipment = Shipment::firstOrCreate(
        ['order_id' => $orderWithShipment->id],
        ['carrier' => 'J&T Express', 'status' => 'processing']
    );

    app(CancelOrderAction::class)->execute($orderWithShipment, $admin, 'Customer cancellation request');

    expect($orderWithShipment->fresh()->order_status)->toBe('cancelled');
    expect($shipment->fresh()->status)->toBe(Shipment::STATUS_CANCELLED);

    $shipmentCancelLog = AdminAuditLog::where('target_table', 'shipments')
        ->where('target_id', $shipment->id)
        ->where('action', 'cancel')
        ->first();
    expect($shipmentCancelLog)->not->toBeNull();
    expect($shipmentCancelLog->old_values['status'])->toBe('processing');
    expect($shipmentCancelLog->new_values['status'])->toBe('cancelled');

    // 2. Order WITHOUT shipment (handles safely inside same transaction)
    $orderWithoutShipment = phase1CreateOrder();
    Shipment::where('order_id', $orderWithoutShipment->id)->delete();
    $orderWithoutShipment->unsetRelation('shipment');

    app(CancelOrderAction::class)->execute($orderWithoutShipment, $admin, 'Cancelled prior to shipment creation');
    expect($orderWithoutShipment->fresh()->order_status)->toBe('cancelled');

    // No shipment cancel audit log created since there was no shipment
    $noShipmentLog = AdminAuditLog::where('target_table', 'shipments')
        ->where('target_id', null)
        ->first();
    expect($noShipmentLog)->toBeNull();
});

test('1.5c: ShipmentService blocks moving unpaid non-COD order to shipped, out_for_delivery, or delivered', function () {
    $unpaidOrder = phase1CreateOrder([
        'order_status' => 'confirmed',
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
    ]);
    $shipment = Shipment::firstOrCreate(
        ['order_id' => $unpaidOrder->id],
        ['carrier' => 'J&T Express', 'status' => 'order_confirmed']
    );

    $service = app(ShipmentService::class);

    // Blocked on 'shipped'
    expect(fn() => $service->updateStatus($shipment, Shipment::STATUS_SHIPPED))
        ->toThrow(InvalidArgumentException::class);

    // Blocked on 'out_for_delivery'
    expect(fn() => $service->updateStatus($shipment, Shipment::STATUS_OUT_FOR_DELIVERY))
        ->toThrow(InvalidArgumentException::class);

    // Blocked on 'delivered'
    expect(fn() => $service->updateStatus($shipment, Shipment::STATUS_DELIVERED))
        ->toThrow(InvalidArgumentException::class);
});

test('1.5d: image reorder logs reorder action when display_order changes', function () {
    $admin = phase1Admin();
    $product = phase1CreateProduct('Gallery Tee', 40.00);

    $image = ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'storage/products/gallery.png',
        'is_primary' => true,
        'display_order' => 1,
    ]);

    // Update display_order from 1 to 5
    $this->actingAs($admin)->put("/admin/images/{$image->id}", [
        'display_order' => 5,
    ]);

    $reorderLog = AdminAuditLog::where('target_table', 'product_images')
        ->where('target_id', $image->id)
        ->where('action', 'reorder')
        ->first();

    expect($reorderLog)->not->toBeNull();
    expect($reorderLog->old_values['display_order'])->toBe(1);
    expect($reorderLog->new_values['display_order'])->toBe(5);
});

test('1.5e: boot-time guard logs critical error and refuses to boot if production has payment simulation enabled', function () {
    \Illuminate\Support\Facades\Log::spy();

    // Set production env and allow_payment_simulation = true
    app()->detectEnvironment(fn() => 'production');
    config(['hoyos.allow_payment_simulation' => true]);

    $provider = new \App\Providers\AppServiceProvider(app());

    expect(fn() => $provider->boot())->toThrow(\RuntimeException::class);

    \Illuminate\Support\Facades\Log::shouldHaveReceived('critical')
        ->withArgs(fn($msg) => str_contains($msg, 'Payment simulation is enabled in production environment'));

    // Reset environment
    app()->detectEnvironment(fn() => 'testing');
    config(['hoyos.allow_payment_simulation' => false]);
});

test('1.5f: admin mark-paid refuses cancelled order and non-pending payment', function () {
    $admin = phase1Admin();
    $controller = app(\App\Http\Controllers\Admin\PaymentController::class);

    // 1. Cancelled order with COD
    $cancelledOrder = phase1CreateOrder([
        'order_status' => 'cancelled',
        'payment_method' => 'cod',
        'payment_status' => 'cancelled',
    ]);
    $cancelledPayment = Payment::create([
        'order_id' => $cancelledOrder->id,
        'payment_method' => 'cod',
        'amount' => $cancelledOrder->total_amount,
        'currency' => 'USD',
        'status' => 'pending',
    ]);

    $resCancelled = $this->actingAs($admin)->post("/admin/payments/{$cancelledPayment->id}/mark-paid");
    $resCancelled->assertSessionHas('error', 'Cannot mark payment as paid for a cancelled order.');
    expect($cancelledPayment->fresh()->status)->toBe('pending');

    // 2. Expired payment
    $orderWithExpiredPayment = phase1CreateOrder([
        'order_status' => 'shipped',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);
    $expiredPayment = Payment::create([
        'order_id' => $orderWithExpiredPayment->id,
        'payment_method' => 'cod',
        'amount' => $orderWithExpiredPayment->total_amount,
        'currency' => 'USD',
        'status' => 'expired',
    ]);

    $resExpired = $this->actingAs($admin)->post("/admin/payments/{$expiredPayment->id}/mark-paid");
    $resExpired->assertSessionHas('error');
    expect($expiredPayment->fresh()->status)->toBe('expired');
});

test('1.5g: audit UI humanizes reorder, cancel, demote_primary, and promote_primary without raw snake_case', function () {
    $admin = phase1Admin();

    expect(\App\Support\LabelHelper::humanize('reorder'))->toBe('Reorder');
    expect(\App\Support\LabelHelper::humanize('cancel'))->toBe('Cancel');
    expect(\App\Support\LabelHelper::humanize('demote_primary'))->toBe('Demote primary');
    expect(\App\Support\LabelHelper::humanize('promote_primary'))->toBe('Promote primary');

    // Write audit logs with these verbs
    \App\Services\AdminAuditLogger::log('images', 'demote_primary', 'product_images', 101, ['is_primary' => true], ['is_primary' => false]);
    \App\Services\AdminAuditLogger::log('images', 'promote_primary', 'product_images', 102, ['is_primary' => false], ['is_primary' => true]);
    \App\Services\AdminAuditLogger::log('images', 'reorder', 'product_images', 103, ['display_order' => 1], ['display_order' => 2]);
    \App\Services\AdminAuditLogger::log('shipments', 'cancel', 'shipments', 104, ['status' => 'processing'], ['status' => 'cancelled']);

    $response = $this->actingAs($admin)->get('/admin/audit-logs');
    $response->assertOk();
    $response->assertSee('Demote primary');
    $response->assertSee('Promote primary');
    $response->assertSee('Reorder');
    $response->assertSee('Cancel');
    $response->assertDontSee('>demote_primary<', false);
    $response->assertDontSee('>promote_primary<', false);
});

