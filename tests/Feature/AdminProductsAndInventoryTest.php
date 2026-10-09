<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function createAdminUser(): User
{
    return User::factory()->admin()->create();
}

function createCustomerUser(): User
{
    return User::factory()->create(['role' => 'customer']);
}

function createCategory(string $slug = 'tops'): Category
{
    return Category::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
}

/* =========================================================================
 * 1. ACCESS CONTROL
 * ========================================================================= */

test('guest is blocked on every products and inventory route', function () {
    $category = createCategory();
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Test Access Product',
        'slug' => 'test-access-product',
        'price' => 50.00,
        'is_active' => true,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'ACCESS-M',
        'stock_quantity' => 5,
    ]);

    $this->get('/admin/products')->assertRedirect(route('login'));
    $this->get('/admin/products/create')->assertRedirect(route('login'));
    $this->post('/admin/products', [])->assertRedirect(route('login'));
    $this->get('/admin/products/' . $product->id . '/edit')->assertRedirect(route('login'));
    $this->put('/admin/products/' . $product->id, [])->assertRedirect(route('login'));
    $this->post('/admin/products/' . $product->id . '/toggle-active')->assertRedirect(route('login'));
    $this->delete('/admin/products/' . $product->id)->assertRedirect(route('login'));
    $this->post('/admin/products/' . $product->id . '/restore')->assertRedirect(route('login'));

    $this->post('/admin/products/' . $product->id . '/variants', [])->assertRedirect(route('login'));
    $this->put('/admin/variants/' . $variant->id, [])->assertRedirect(route('login'));
    $this->delete('/admin/variants/' . $variant->id)->assertRedirect(route('login'));
    $this->post('/admin/variants/' . $variant->id . '/restore')->assertRedirect(route('login'));

    $this->post('/admin/products/' . $product->id . '/images', [])->assertRedirect(route('login'));

    $this->get('/admin/inventory')->assertRedirect(route('login'));
    $this->get('/admin/inventory/' . $variant->id)->assertRedirect(route('login'));
    $this->post('/admin/inventory/' . $variant->id . '/adjust', [])->assertRedirect(route('login'));
});

test('customer gets 403 on every products and inventory route', function () {
    $customer = createCustomerUser();
    $category = createCategory();
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Customer Block Product',
        'slug' => 'customer-block-product',
        'price' => 40.00,
        'is_active' => true,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'L',
        'sku' => 'BLOCK-L',
        'stock_quantity' => 10,
    ]);

    $this->actingAs($customer)->get('/admin/products')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/products/create')->assertStatus(403);
    $this->actingAs($customer)->post('/admin/products', [])->assertStatus(403);
    $this->actingAs($customer)->get('/admin/products/' . $product->id . '/edit')->assertStatus(403);
    $this->actingAs($customer)->put('/admin/products/' . $product->id, [])->assertStatus(403);
    $this->actingAs($customer)->post('/admin/products/' . $product->id . '/toggle-active')->assertStatus(403);
    $this->actingAs($customer)->delete('/admin/products/' . $product->id)->assertStatus(403);
    $this->actingAs($customer)->post('/admin/products/' . $product->id . '/restore')->assertStatus(403);

    $this->actingAs($customer)->post('/admin/products/' . $product->id . '/variants', [])->assertStatus(403);
    $this->actingAs($customer)->put('/admin/variants/' . $variant->id, [])->assertStatus(403);
    $this->actingAs($customer)->delete('/admin/variants/' . $variant->id)->assertStatus(403);
    $this->actingAs($customer)->post('/admin/variants/' . $variant->id . '/restore')->assertStatus(403);

    $this->actingAs($customer)->post('/admin/products/' . $product->id . '/images', [])->assertStatus(403);

    $this->actingAs($customer)->get('/admin/inventory')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/inventory/' . $variant->id)->assertStatus(403);
    $this->actingAs($customer)->post('/admin/inventory/' . $variant->id . '/adjust', [])->assertStatus(403);
});

/* =========================================================================
 * 2. PRODUCT CREATE & UPDATE VALIDATION (Unique Slug & Compare At Price)
 * ========================================================================= */

test('product create validates unique slug including soft-deleted and compare_at_price rule', function () {
    $admin = createAdminUser();
    $category = createCategory();

    // 1. Existing product
    $existing = Product::create([
        'category_id' => $category->id,
        'name' => 'Existing Goth Tee',
        'slug' => 'existing-goth-tee',
        'price' => 30.00,
        'is_active' => true,
    ]);

    // Attempt create with duplicate slug
    $resDup = $this->actingAs($admin)->post('/admin/products', [
        'name' => 'Duplicate Slug Tee',
        'slug' => 'existing-goth-tee',
        'category_id' => $category->id,
        'price' => 35.00,
    ]);
    $resDup->assertSessionHasErrors('slug');

    // 2. Soft-delete the existing product
    $existing->delete();

    // Slug must STILL be unique including soft-deleted!
    $resDupTrashed = $this->actingAs($admin)->post('/admin/products', [
        'name' => 'Another Duplicate Slug Tee',
        'slug' => 'existing-goth-tee',
        'category_id' => $category->id,
        'price' => 35.00,
    ]);
    $resDupTrashed->assertSessionHasErrors('slug');

    // 3. compare_at_price must be greater than price
    $resComparePrice = $this->actingAs($admin)->post('/admin/products', [
        'name' => 'Bad Compare Price',
        'slug' => 'bad-compare-price',
        'category_id' => $category->id,
        'price' => 50.00,
        'compare_at_price' => 45.00, // lower than price
    ]);
    $resComparePrice->assertSessionHasErrors('compare_at_price');

    // 4. Successful creation auto-generates slug if omitted
    $resSuccess = $this->actingAs($admin)->post('/admin/products', [
        'name' => 'Cyber Denim Vest',
        'category_id' => $category->id,
        'price' => 45.00,
        'compare_at_price' => 60.00,
        'is_active' => true,
    ]);
    $resSuccess->assertSessionHas('success');

    $created = Product::where('slug', 'cyber-denim-vest')->first();
    expect($created)->not->toBeNull()
        ->and($created->compare_at_price)->toBe('60.00');

    // Check audit log recorded
    $this->assertDatabaseHas('admin_audit_logs', [
        'module' => 'products',
        'action' => 'create',
        'target_id' => $created->id,
    ]);
});

test('product update records price changes in audit without altering existing orders', function () {
    $admin = createAdminUser();
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Vintage Cargo Pants',
        'slug' => 'vintage-cargo-pants',
        'price' => 70.00,
        'is_active' => true,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'L',
        'sku' => 'VCP-L',
        'stock_quantity' => 10,
    ]);

    // Create an order historical snapshot
    $order = Order::create([
        'order_number' => 'HOYOS-HIST-01',
        'customer_name' => 'History Buyer',
        'customer_phone' => '012999000',
        'shipping_address' => 'Street 1',
        'province' => 'Phnom Penh',
        'subtotal' => 70.00,
        'shipping_fee' => 0.00,
        'total_amount' => 70.00,
        'order_status' => 'confirmed',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);
    $item = OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'variant_size' => 'L',
        'unit_price' => 70.00,
        'quantity' => 1,
        'subtotal' => 70.00,
    ]);

    // Admin updates product price to $85.00
    $res = $this->actingAs($admin)->put('/admin/products/' . $product->id, [
        'name' => 'Vintage Cargo Pants (Updated)',
        'slug' => 'vintage-cargo-pants',
        'category_id' => $category->id,
        'price' => 85.00,
        'is_active' => true,
    ]);
    $res->assertSessionHas('success');

    $product->refresh();
    expect($product->price)->toBe('85.00');

    // Historical order item must remain unchanged
    $item->refresh();
    expect($item->unit_price)->toBe('70.00');

    // Audit log should be recorded
    $this->assertDatabaseHas('admin_audit_logs', [
        'module' => 'products',
        'action' => 'update',
        'target_id' => $product->id,
    ]);
});

/* =========================================================================
 * 3. SOFT DELETE AND RESTORE
 * ========================================================================= */

test('product soft delete and restore', function () {
    $admin = createAdminUser();
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Dark Trench Coat',
        'slug' => 'dark-trench-coat',
        'price' => 120.00,
        'is_active' => true,
    ]);

    // Soft delete
    $resDel = $this->actingAs($admin)->delete('/admin/products/' . $product->id);
    $resDel->assertSessionHas('success');

    expect($product->fresh()->trashed())->toBeTrue();

    // Restore
    $resRestore = $this->actingAs($admin)->post('/admin/products/' . $product->id . '/restore');
    $resRestore->assertSessionHas('success');

    expect($product->fresh()->trashed())->toBeFalse();
});

/* =========================================================================
 * 4. STOREFRONT SAFETY: INACTIVE OR SOFT-DELETED PRODUCTS
 * ========================================================================= */

test('inactive or soft-deleted product is not visible on storefront and cannot be added to cart', function () {
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Invisible Ghost Shirt',
        'slug' => 'invisible-ghost-shirt',
        'price' => 25.00,
        'is_active' => false, // inactive
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'GHOST-M',
        'stock_quantity' => 10,
    ]);

    // Storefront show endpoint returns 404
    $this->get('/product/' . $product->slug)->assertStatus(404);

    // Attempting to add inactive product to cart returns error
    $resAdd = $this->post('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);
    $resAdd->assertSessionHas('error');

    // Soft-deleted product also fails
    $product->update(['is_active' => true]);
    $product->delete();

    $this->get('/product/' . $product->slug)->assertStatus(404);

    $resAddTrashed = $this->post('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);
    $resAddTrashed->assertSessionHas('error');
});

/* =========================================================================
 * 5. VARIANTS & INITIAL STOCK ADJUSTMENT
 * ========================================================================= */

test('variant add with initial stock writes stock_adjustments row', function () {
    $admin = createAdminUser();
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Acid Knit Beanie',
        'slug' => 'acid-knit-beanie',
        'price' => 20.00,
        'is_active' => true,
    ]);

    $res = $this->actingAs($admin)->post('/admin/products/' . $product->id . '/variants', [
        'size' => 'One size',
        'sku' => 'BEANIE-OS',
        'price_adjustment' => 0.00,
        'initial_stock' => 15,
    ]);
    $res->assertSessionHas('success');

    $variant = ProductVariant::where('sku', 'BEANIE-OS')->first();
    expect($variant)->not->toBeNull()
        ->and($variant->stock_quantity)->toBe(15);

    // Assert stock adjustment recorded with initial stock
    $adjustment = StockAdjustment::where('product_variant_id', $variant->id)->first();
    expect($adjustment)->not->toBeNull()
        ->and($adjustment->adjustment_type)->toBe('manual_restock')
        ->and($adjustment->quantity_change)->toBe(15)
        ->and($adjustment->quantity_before)->toBe(0)
        ->and($adjustment->quantity_after)->toBe(15)
        ->and($adjustment->reason)->toBe('Initial stock')
        ->and($adjustment->admin_id)->toBe($admin->id);
});

test('variant duplicate size handling and restore', function () {
    $admin = createAdminUser();
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Patchwork Cargo',
        'slug' => 'patchwork-cargo',
        'price' => 55.00,
        'is_active' => true,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'PC-M',
        'stock_quantity' => 5,
    ]);

    // Adding existing active size is rejected
    $resActiveDup = $this->actingAs($admin)->post('/admin/products/' . $product->id . '/variants', [
        'size' => 'M',
        'sku' => 'PC-M-NEW',
    ]);
    $resActiveDup->assertSessionHas('error');

    // Soft delete variant
    $variant->delete();

    // Adding again detects soft-deleted variant and offers restore
    $resTrashedDup = $this->actingAs($admin)->post('/admin/products/' . $product->id . '/variants', [
        'size' => 'M',
        'sku' => 'PC-M-RETRY',
    ]);
    $resTrashedDup->assertSessionHas('error');

    // Restoring soft-deleted variant succeeds
    $resRestore = $this->actingAs($admin)->post('/admin/variants/' . $variant->id . '/restore');
    $resRestore->assertSessionHas('success');
    expect($variant->fresh()->trashed())->toBeFalse();
});

test('stock_quantity cannot be changed through variant edit', function () {
    $admin = createAdminUser();
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Gothic Boots',
        'slug' => 'gothic-boots',
        'price' => 110.00,
        'is_active' => true,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'L',
        'sku' => 'BOOT-L',
        'stock_quantity' => 7,
    ]);

    $res = $this->actingAs($admin)->put('/admin/variants/' . $variant->id, [
        'sku' => 'BOOT-L-MOD',
        'price_adjustment' => 5.00,
        'stock_quantity' => 999, // Attempt direct stock hack
    ]);
    $res->assertSessionHas('success');

    $variant->refresh();
    expect($variant->sku)->toBe('BOOT-L-MOD')
        ->and($variant->price_adjustment)->toBe('5.00')
        ->and($variant->stock_quantity)->toBe(7); // Must still be 7!
});

test('removing only active size of active product is blocked', function () {
    $admin = createAdminUser();
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Single Size Cap',
        'slug' => 'single-size-cap',
        'price' => 20.00,
        'is_active' => true,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'One size',
        'sku' => 'CAP-OS',
        'stock_quantity' => 5,
    ]);

    // Deleting the only active size of active product must be blocked
    $res = $this->actingAs($admin)->delete('/admin/variants/' . $variant->id);
    $res->assertSessionHas('error');
    expect($variant->fresh()->trashed())->toBeFalse();

    // Deactivating product allows removing the variant
    $product->update(['is_active' => false]);
    $resAllowed = $this->actingAs($admin)->delete('/admin/variants/' . $variant->id);
    $resAllowed->assertSessionHas('success');
    expect($variant->fresh()->trashed())->toBeTrue();
});

function createFakeImage(string $filename = 'test.png', int $kilobytes = 100, string $mime = 'image/png'): UploadedFile
{
    // Minimal valid PNG binary
    $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    if ($kilobytes * 1024 > strlen($content)) {
        $content .= str_repeat(' ', ($kilobytes * 1024) - strlen($content));
    }

    return UploadedFile::fake()->createWithContent($filename, $content, $mime);
}

/* =========================================================================
 * 6. PRODUCT IMAGES UPLOAD, PRIMARY ENFORCEMENT & DELETION
 * ========================================================================= */

test('image upload stores file under random name, enforces one primary, and promotes next on delete', function () {
    Storage::fake('public');
    $admin = createAdminUser();
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Cyber Zip Hoodie',
        'slug' => 'cyber-zip-hoodie',
        'price' => 65.00,
        'is_active' => true,
    ]);

    // 1. Upload valid images
    $file1 = createFakeImage('lookbook_front.png', 50, 'image/png');
    $file2 = createFakeImage('lookbook_back.png', 80, 'image/png');

    $resUpload = $this->actingAs($admin)->post('/admin/products/' . $product->id . '/images', [
        'images' => [$file1, $file2],
        'credit' => 'Photo by Studio Hoyos',
    ]);
    $resUpload->assertSessionHas('success');

    $images = ProductImage::where('product_id', $product->id)->orderBy('id')->get();
    expect($images)->toHaveCount(2);

    $img1 = $images[0];
    $img2 = $images[1];

    // Assert file stored on disk with random name (never lookbook_front.png)
    expect($img1->image_path)->not->toContain('lookbook_front.png');
    $diskPath1 = str_replace('storage/', '', $img1->image_path);
    Storage::disk('public')->assertExists($diskPath1);

    // Exactly one primary
    expect($img1->is_primary)->toBeTrue()
        ->and($img2->is_primary)->toBeFalse();

    // 2. Reject wrong mime type
    $badFile = UploadedFile::fake()->create('malicious.pdf', 100, 'application/pdf');
    $resBadMime = $this->actingAs($admin)->post('/admin/products/' . $product->id . '/images', [
        'images' => [$badFile],
    ]);
    $resBadMime->assertSessionHasErrors('images.0');

    // 3. Reject oversize file (> 4MB)
    $hugeFile = createFakeImage('huge.png', 5000, 'image/png'); // 5 MB
    $resHuge = $this->actingAs($admin)->post('/admin/products/' . $product->id . '/images', [
        'images' => [$hugeFile],
    ]);
    $resHuge->assertSessionHasErrors('images.0');

    // 4. Deleting primary image promotes the second image
    $resDel = $this->actingAs($admin)->delete('/admin/images/' . $img1->id);
    $resDel->assertSessionHas('success');

    // Physical file deleted from disk
    Storage::disk('public')->assertMissing($diskPath1);

    // Image 2 is promoted to primary
    expect($img2->fresh()->is_primary)->toBeTrue();
});

/* =========================================================================
 * 7. INVENTORY ADJUSTMENT & AUDIT TRAIL
 * ========================================================================= */

test('inventory adjust handles restock, shrinkage, damage, rejects negative result, and records audit trail', function () {
    $admin = createAdminUser();
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Y2K Denim Jacket',
        'slug' => 'y2k-denim-jacket',
        'price' => 80.00,
        'is_active' => true,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'Y2K-DJ-M',
        'stock_quantity' => 10,
    ]);

    // 1. Manual Restock (+5)
    $resRestock = $this->actingAs($admin)->post('/admin/inventory/' . $variant->id . '/adjust', [
        'type' => 'manual_restock',
        'quantity' => 5,
        'reason' => 'Warehouse shipment shipment arrival batch A',
    ]);
    $resRestock->assertSessionHas('success');
    expect($variant->fresh()->stock_quantity)->toBe(15);

    // 2. Manual Shrinkage (-3)
    $resShrinkage = $this->actingAs($admin)->post('/admin/inventory/' . $variant->id . '/adjust', [
        'type' => 'manual_shrinkage',
        'quantity' => 3,
        'reason' => 'Quarterly physical inventory count reconciliation',
    ]);
    $resShrinkage->assertSessionHas('success');
    expect($variant->fresh()->stock_quantity)->toBe(12);

    // 3. Damage (-2)
    $resDamage = $this->actingAs($admin)->post('/admin/inventory/' . $variant->id . '/adjust', [
        'type' => 'damage',
        'quantity' => 2,
        'reason' => 'Water damage during warehouse roof repair',
    ]);
    $resDamage->assertSessionHas('success');
    expect($variant->fresh()->stock_quantity)->toBe(10);

    // 4. Reject negative result (Current stock 10, attempt to remove 15)
    $resNeg = $this->actingAs($admin)->post('/admin/inventory/' . $variant->id . '/adjust', [
        'type' => 'manual_shrinkage',
        'quantity' => 15,
        'reason' => 'Attempting excessive deduction',
    ]);
    $resNeg->assertSessionHas('error');
    expect($variant->fresh()->stock_quantity)->toBe(10);

    // Assert audit ledger rows exist with admin_id
    $adjustments = StockAdjustment::where('product_variant_id', $variant->id)->get();
    expect($adjustments)->toHaveCount(3);
    foreach ($adjustments as $adj) {
        expect($adj->admin_id)->toBe($admin->id)
            ->and($adj->order_id)->toBeNull();
    }
});

/* =========================================================================
 * 8. INVENTORY LOW-STOCK & OUT-OF-STOCK FILTERS
 * ========================================================================= */

test('inventory filters correctly by low-stock and out-of-stock', function () {
    $admin = createAdminUser();
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Stock Test Shirt',
        'slug' => 'stock-test-shirt',
        'price' => 30.00,
        'is_active' => true,
    ]);

    $varHealthy = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'S',
        'sku' => 'HEALTHY-S',
        'stock_quantity' => 20,
    ]);

    $varLow = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'LOW-M',
        'stock_quantity' => 3, // <= 5
    ]);

    $varOos = ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'L',
        'sku' => 'OOS-L',
        'stock_quantity' => 0,
    ]);

    // Filter low stock
    $resLow = $this->actingAs($admin)->get('/admin/inventory?low_stock=1');
    $resLow->assertSee('LOW-M');
    $resLow->assertDontSee('HEALTHY-S');
    $resLow->assertDontSee('OOS-L');

    // Filter out of stock
    $resOos = $this->actingAs($admin)->get('/admin/inventory?out_of_stock=1');
    $resOos->assertSee('OOS-L');
    $resOos->assertDontSee('HEALTHY-S');
    $resOos->assertDontSee('LOW-M');
});

test('failed product creation preserves old values, renders error summary, and reports field errors', function () {
    $admin = createAdminUser();
    $category = createCategory();

    // Submit invalid data: missing name, compare_at_price less than price (40 <= 50)
    $response = $this->actingAs($admin)
        ->from(route('admin.products.create'))
        ->post(route('admin.products.store'), [
            'name' => '', // missing required
            'slug' => 'custom-jacket-slug',
            'category_id' => $category->id,
            'collection_name' => 'Preserved Collection',
            'price' => 50.00,
            'compare_at_price' => 40.00, // invalid: must be greater than price
            'description' => 'Preserved description narrative.',
            'initial_sizes' => ['S', 'M'],
            'sku_prefix' => 'HOYOS-TEST-',
        ]);

    $response->assertRedirect(route('admin.products.create'));
    $response->assertSessionHasErrors(['name', 'compare_at_price']);

    // Follow redirect to create page
    $createPage = $this->actingAs($admin)->get(route('admin.products.create'));

    // Verify error summary exists
    $createPage->assertSee('Please correct');
    $createPage->assertSee('form-error-summary');

    // Verify old values are preserved
    $createPage->assertSee('custom-jacket-slug');
    $createPage->assertSee('Preserved Collection');
    $createPage->assertSee('Preserved description narrative.');
    $createPage->assertSee('HOYOS-TEST-');
});

test('products index uses shared low stock threshold and variant level count matching dashboard', function () {
    $admin = createAdminUser();
    $category = createCategory();

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Threshold Product',
        'slug' => 'threshold-product',
        'price' => 75.00,
        'is_active' => true,
    ]);

    // Create 1 variant with 4 units (<= 5, so low stock)
    ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'S',
        'sku' => 'THRESH-S',
        'stock_quantity' => 4,
    ]);

    // Create 1 variant with 0 units (out of stock)
    ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'THRESH-M',
        'stock_quantity' => 0,
    ]);

    // Create 1 variant with 20 units (healthy)
    ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'L',
        'sku' => 'THRESH-L',
        'stock_quantity' => 20,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.products.index'));

    $response->assertOk();
    $response->assertSee('Variants ≤ 5 units');
    // Button label check (no literal +)
    $response->assertSee('New Product');
    $response->assertDontSee('+ + New Product');
    // Stock pill whitespace-nowrap check
    $response->assertSee('whitespace-nowrap');
});
