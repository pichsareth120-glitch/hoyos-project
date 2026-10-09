<?php

use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

function createAdmin(): User
{
    return User::factory()->admin()->create();
}

function createCustomer(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'customer',
        'account_status' => 'active',
    ], $attributes));
}

function createTestProductVariant(int $stock = 10): ProductVariant
{
    $category = Category::firstOrCreate(['slug' => 'test-cat'], ['name' => 'Test Cat']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Test Product ' . uniqid(),
        'slug' => 'test-product-' . uniqid(),
        'price' => 50.00,
        'is_active' => true,
    ]);

    return ProductVariant::create([
        'product_id' => $product->id,
        'size' => 'M',
        'sku' => 'SKU-' . uniqid(),
        'stock_quantity' => $stock,
    ]);
}

/* =========================================================================
 * 1. ACCESS CONTROL
 * ========================================================================= */

test('guest is blocked on every customers and audit logs route', function () {
    $customer = createCustomer();
    $log = AdminAuditLog::create([
        'module' => 'orders',
        'action' => 'update',
        'target_table' => 'orders',
        'target_id' => 1,
        'created_at' => now(),
    ]);

    $this->get('/admin/customers')->assertRedirect(route('login'));
    $this->get('/admin/customers/' . $customer->id)->assertRedirect(route('login'));
    $this->post('/admin/customers/' . $customer->id . '/suspend', ['reason' => 'Test reason'])->assertRedirect(route('login'));
    $this->post('/admin/customers/' . $customer->id . '/activate')->assertRedirect(route('login'));
    $this->get('/admin/audit-logs')->assertRedirect(route('login'));
    $this->get('/admin/audit-logs/' . $log->id)->assertRedirect(route('login'));
});

test('customer role user is forbidden (403) on every customers and audit logs route', function () {
    $actingCustomer = createCustomer();
    $targetCustomer = createCustomer();
    $log = AdminAuditLog::create([
        'module' => 'orders',
        'action' => 'update',
        'target_table' => 'orders',
        'target_id' => 1,
        'created_at' => now(),
    ]);

    $this->actingAs($actingCustomer)->get('/admin/customers')->assertStatus(403);
    $this->actingAs($actingCustomer)->get('/admin/customers/' . $targetCustomer->id)->assertStatus(403);
    $this->actingAs($actingCustomer)->post('/admin/customers/' . $targetCustomer->id . '/suspend', ['reason' => 'Test reason'])->assertStatus(403);
    $this->actingAs($actingCustomer)->post('/admin/customers/' . $targetCustomer->id . '/activate')->assertStatus(403);
    $this->actingAs($actingCustomer)->get('/admin/audit-logs')->assertStatus(403);
    $this->actingAs($actingCustomer)->get('/admin/audit-logs/' . $log->id)->assertStatus(403);
});

/* =========================================================================
 * 2. CUSTOMERS DIRECTORY & ADMIN EXCLUSION
 * ========================================================================= */

test('admins never appear in the customers list and /admin/customers/{admin} returns 404', function () {
    $admin = createAdmin();
    $otherAdmin = createAdmin();
    $customer = createCustomer(['name' => 'Real Customer']);

    $response = $this->actingAs($admin)->get('/admin/customers');
    $response->assertOk();
    $response->assertSee('Real Customer');
    $response->assertDontSee($otherAdmin->email);

    // Visiting admin ID in customers module must 404
    $this->actingAs($admin)->get('/admin/customers/' . $otherAdmin->id)->assertNotFound();
    $this->actingAs($admin)->get('/admin/customers/' . $admin->id)->assertNotFound();
});

test('lifetime spend counts only paid orders and excludes refunded and cancelled orders', function () {
    $admin = createAdmin();
    $customer = createCustomer(['name' => 'Metric Customer']);

    // Order 1: Paid & Confirmed ($100.00) -> INCLUDED
    Order::create([
        'order_number' => 'ORD-101',
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_phone' => '012111222',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 100.00,
        'total_amount' => 100.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'confirmed',
    ]);

    // Order 2: Paid & Delivered ($50.00) -> INCLUDED
    Order::create([
        'order_number' => 'ORD-102',
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_phone' => '012111222',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 50.00,
        'total_amount' => 50.00,
        'payment_method' => 'cod',
        'payment_status' => 'paid',
        'order_status' => 'delivered',
    ]);

    // Order 3: Paid but Cancelled ($30.00) -> EXCLUDED
    Order::create([
        'order_number' => 'ORD-103',
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_phone' => '012111222',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 30.00,
        'total_amount' => 30.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'paid',
        'order_status' => 'cancelled',
    ]);

    // Order 4: Refunded ($40.00) -> EXCLUDED
    Order::create([
        'order_number' => 'ORD-104',
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_phone' => '012111222',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 40.00,
        'total_amount' => 40.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'refunded',
        'order_status' => 'confirmed',
    ]);

    // Order 5: Pending ($60.00) -> EXCLUDED
    Order::create([
        'order_number' => 'ORD-105',
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_phone' => '012111222',
        'shipping_address' => 'Phnom Penh',
        'province' => 'Phnom Penh',
        'subtotal' => 60.00,
        'total_amount' => 60.00,
        'payment_method' => 'aba_khqr',
        'payment_status' => 'pending',
        'order_status' => 'confirmed',
    ]);

    // Index page check
    $indexResponse = $this->actingAs($admin)->get('/admin/customers');
    $indexResponse->assertOk();
    $indexResponse->assertSee('$150.00');

    // Show page check
    $showResponse = $this->actingAs($admin)->get('/admin/customers/' . $customer->id);
    $showResponse->assertOk();
    $showResponse->assertSee('$150.00');
    // AOV check: 150.00 / 2 paid orders = $75.00
    $showResponse->assertSee('$75.00');
    // Total order count = 5
    $showResponse->assertSee('Total Orders Placed');
});

test('search and filters work properly on customers index', function () {
    $admin = createAdmin();
    $customerA = createCustomer([
        'name' => 'Alice Wonderland',
        'email' => 'alice@wonderland.test',
        'phone' => '012345678',
        'account_status' => 'active',
    ]);

    $customerB = createCustomer([
        'name' => 'Bob Builder',
        'email' => 'bob@builder.test',
        'phone' => '098765432',
        'account_status' => 'suspended',
    ]);

    // Search by name
    $this->actingAs($admin)->get('/admin/customers?search=Alice')
        ->assertSee('Alice Wonderland')
        ->assertDontSee('Bob Builder');

    // Search by email
    $this->actingAs($admin)->get('/admin/customers?search=bob@builder.test')
        ->assertSee('Bob Builder')
        ->assertDontSee('Alice Wonderland');

    // Search by phone
    $this->actingAs($admin)->get('/admin/customers?search=012345678')
        ->assertSee('Alice Wonderland')
        ->assertDontSee('Bob Builder');

    // Filter by status suspended
    $this->actingAs($admin)->get('/admin/customers?status=suspended')
        ->assertSee('Bob Builder')
        ->assertDontSee('Alice Wonderland');

    // Filter by status active
    $this->actingAs($admin)->get('/admin/customers?status=active')
        ->assertSee('Alice Wonderland')
        ->assertDontSee('Bob Builder');
});

/* =========================================================================
 * 3. SUSPEND & ACTIVATE RULES
 * ========================================================================= */

test('suspend requires reason, rejects admin target, rejects self, and rejects already suspended', function () {
    $admin = createAdmin();
    $otherAdmin = createAdmin();
    $customer = createCustomer(['account_status' => 'active']);

    // Reason required min 5 chars
    $this->actingAs($admin)->post('/admin/customers/' . $customer->id . '/suspend', ['reason' => 'abc'])
        ->assertSessionHasErrors('reason');

    // Cannot target an admin
    $this->actingAs($admin)->post('/admin/customers/' . $otherAdmin->id . '/suspend', ['reason' => 'Admin violation'])
        ->assertStatus(403);

    // Cannot target self
    $this->actingAs($admin)->post('/admin/customers/' . $admin->id . '/suspend', ['reason' => 'Self suspend'])
        ->assertSessionHas('error');

    // Suspend successfully
    $this->actingAs($admin)->post('/admin/customers/' . $customer->id . '/suspend', ['reason' => 'Chargeback suspicious activity'])
        ->assertSessionHas('success');

    $customer->refresh();
    expect($customer->account_status)->toBe('suspended');

    // Idempotent: repeating suspend on already suspended user rejects with message
    $this->actingAs($admin)->post('/admin/customers/' . $customer->id . '/suspend', ['reason' => 'Second attempt'])
        ->assertSessionHas('error');
});

test('suspended customer cannot log in and receives generic message', function () {
    $customer = createCustomer([
        'email' => 'locked@client.test',
        'password' => Hash::make('password123'),
        'account_status' => 'suspended',
    ]);

    $response = $this->post('/login', [
        'email' => 'locked@client.test',
        'password' => 'password123',
    ]);

    // Must be unauthenticated
    expect(Auth::check())->toBeFalse();

    // Must have validation error for email with generic suspended message
    $response->assertSessionHasErrors('email');
    $errors = session('errors')->get('email');
    expect($errors[0])->toContain('suspended');
    expect($errors[0])->not->toContain('by admin');
});

test('already logged-in suspended user is logged out on next request via middleware', function () {
    $customer = createCustomer(['account_status' => 'active']);

    // User is logged in
    $this->actingAs($customer);
    expect(Auth::check())->toBeTrue();

    // Admin suspends user in background
    $customer->forceFill(['account_status' => 'suspended'])->save();

    // Next request to a web route
    $response = $this->get('/dashboard');

    // Should redirect to login with error and be logged out
    $response->assertRedirect(route('login'));
    expect(Auth::check())->toBeFalse();
    $response->assertSessionHasErrors('email');
});

test('activate restores active status and login ability', function () {
    $admin = createAdmin();
    $customer = createCustomer([
        'email' => 'reinstated@client.test',
        'password' => Hash::make('mypassword123'),
        'account_status' => 'suspended',
    ]);

    // Activate customer
    $this->actingAs($admin)->post('/admin/customers/' . $customer->id . '/activate')
        ->assertSessionHas('success');

    $customer->refresh();
    expect($customer->account_status)->toBe('active');

    // Activating already active customer is rejected idempotently
    $this->actingAs($admin)->post('/admin/customers/' . $customer->id . '/activate')
        ->assertSessionHas('error');

    // Log out admin so login endpoint is accessed as guest
    Auth::logout();

    // Customer can now log in
    $response = $this->post('/login', [
        'email' => 'reinstated@client.test',
        'password' => 'mypassword123',
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(Auth::check())->toBeTrue();
    expect(Auth::id())->toBe($customer->id);
});

test('guest checkout still works while a suspended user exists in database', function () {
    // Create a suspended user in DB
    createCustomer([
        'email' => 'banned@client.test',
        'account_status' => 'suspended',
    ]);

    $variant = createTestProductVariant(10);

    // Create guest cart directly with variant
    $cart = \App\Models\Cart::create(['session_id' => 'test-guest-session', 'user_id' => null]);
    $cart->items()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    // Guest completes checkout with that guest cart in session
    $response = $this->withSession(['guest_cart_id' => $cart->id])->post('/checkout', [
        'customer_name' => 'Guest Shopper',
        'customer_phone' => '012998877',
        'customer_email' => 'unrelated_guest@example.com',
        'province' => 'Phnom Penh',
        'shipping_address' => 'House 44, Street 2004',
        'payment_method' => 'cod',
    ]);

    $order = Order::where('customer_phone', '012998877')->latest()->first();
    expect($order)->not->toBeNull();
    expect($order->guest_token)->not->toBeNull();
    $response->assertRedirect(route('orders.show', [
        'orderNumber' => $order->order_number,
        'token' => $order->guest_token,
    ]));
});

/* =========================================================================
 * 4. AUDIT LOGS, FILTERS, SENSITIVE MASKING & IMMUTABILITY
 * ========================================================================= */

test('audit rows are written with old and new status on suspend and activate', function () {
    $admin = createAdmin();
    $customer = createCustomer(['account_status' => 'active']);

    // Suspend
    $this->actingAs($admin)->post('/admin/customers/' . $customer->id . '/suspend', [
        'reason' => 'Abusive language in dispute',
    ]);

    $suspendLog = AdminAuditLog::where('target_id', $customer->id)
        ->where('action', 'suspend')
        ->first();

    expect($suspendLog)->not->toBeNull();
    expect($suspendLog->module)->toBe('customers');
    expect($suspendLog->admin_id)->toBe($admin->id);
    expect($suspendLog->old_values)->toBe(['account_status' => 'active']);
    expect($suspendLog->new_values['account_status'])->toBe('suspended');
    expect($suspendLog->new_values['reason'])->toBe('Abusive language in dispute');

    // Activate
    $this->actingAs($admin)->post('/admin/customers/' . $customer->id . '/activate');

    $activateLog = AdminAuditLog::where('target_id', $customer->id)
        ->where('action', 'activate')
        ->first();

    expect($activateLog)->not->toBeNull();
    expect($activateLog->module)->toBe('customers');
    expect($activateLog->admin_id)->toBe($admin->id);
    expect($activateLog->old_values)->toBe(['account_status' => 'suspended']);
    expect($activateLog->new_values)->toBe(['account_status' => 'active']);
});

test('audit log viewer index supports filters', function () {
    $admin = createAdmin();

    $log1 = AdminAuditLog::create([
        'admin_id' => $admin->id,
        'module' => 'orders',
        'action' => 'cancel',
        'target_table' => 'orders',
        'target_id' => 99,
        'created_at' => now()->subDay(),
    ]);

    $log2 = AdminAuditLog::create([
        'admin_id' => $admin->id,
        'module' => 'customers',
        'action' => 'suspend',
        'target_table' => 'users',
        'target_id' => 88,
        'created_at' => now(),
    ]);

    // Filter module=orders
    $this->actingAs($admin)->get('/admin/audit-logs?module=orders')
        ->assertSee('Orders #99')
        ->assertDontSee('Users #88');

    // Filter action=suspend
    $this->actingAs($admin)->get('/admin/audit-logs?action=suspend')
        ->assertSee('Users #88')
        ->assertDontSee('Orders #99');

    // Filter target_id=99
    $this->actingAs($admin)->get('/admin/audit-logs?target_id=99')
        ->assertSee('Orders #99')
        ->assertDontSee('Users #88');
});

test('sensitive keys are masked in audit log viewer show diff', function () {
    $admin = createAdmin();

    $log = AdminAuditLog::create([
        'admin_id' => $admin->id,
        'module' => 'auth',
        'action' => 'update',
        'target_table' => 'users',
        'target_id' => 1,
        'old_values' => [
            'password' => 'supersecretpass',
            'remember_token' => 'plain_remember_token_123',
            'public_name' => 'Old Name',
        ],
        'new_values' => [
            'md5_hash' => 'secret_md5_hash_value',
            'qr_string' => 'raw_aba_qr_string_payload',
            'public_name' => 'New Name',
        ],
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get('/admin/audit-logs/' . $log->id);
    $response->assertOk();

    // Sensitive values must NOT appear in output
    $response->assertDontSee('supersecretpass');
    $response->assertDontSee('plain_remember_token_123');
    $response->assertDontSee('secret_md5_hash_value');
    $response->assertDontSee('raw_aba_qr_string_payload');

    // Mask placeholder must appear
    $response->assertSee('********');

    // Normal non-sensitive values must appear
    $response->assertSee('Old Name');
    $response->assertSee('New Name');
});

test('audit logs are immutable and reject PUT, PATCH, DELETE with 405', function () {
    $admin = createAdmin();

    $log = AdminAuditLog::create([
        'admin_id' => $admin->id,
        'module' => 'security',
        'action' => 'audit',
        'target_table' => 'users',
        'target_id' => 1,
        'created_at' => now(),
    ]);

    $this->actingAs($admin)->put('/admin/audit-logs/' . $log->id, ['action' => 'tampered'])->assertStatus(405);
    $this->actingAs($admin)->patch('/admin/audit-logs/' . $log->id, ['action' => 'tampered'])->assertStatus(405);
    $this->actingAs($admin)->delete('/admin/audit-logs/' . $log->id)->assertStatus(405);
});

/* =========================================================================
 * 5. N+1 QUERY SCALING
 * ========================================================================= */

test('number of queries on the customers list does not grow with row count', function () {
    $admin = createAdmin();

    // 1 customer
    $customer1 = createCustomer();
    Order::create([
        'order_number' => 'ORD-TEST-1',
        'user_id' => $customer1->id,
        'customer_name' => $customer1->name,
        'customer_phone' => '012111222',
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

    $this->actingAs($admin)->get('/admin/customers');
    $queryCountSingle = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Now add 15 more customers with orders
    for ($i = 2; $i <= 16; $i++) {
        $c = createCustomer();
        Order::create([
            'order_number' => 'ORD-TEST-' . $i,
            'user_id' => $c->id,
            'customer_name' => $c->name,
            'customer_phone' => '012111222',
            'shipping_address' => 'Phnom Penh',
            'province' => 'Phnom Penh',
            'subtotal' => 25.00,
            'total_amount' => 25.00,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($admin)->get('/admin/customers');
    $queryCountMultiple = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Must be the exact same number of queries (constant time O(1) query count, no N+1)
    expect($queryCountMultiple)->toBe($queryCountSingle);
});
