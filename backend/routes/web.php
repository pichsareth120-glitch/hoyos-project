<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController as AdminProductImageController;
use App\Http\Controllers\Admin\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public Storefront
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/credits', [HomeController::class, 'credits'])->name('credits');
Route::get('/category/{categorySlug?}', [ProductController::class, 'index'])->name('category.show');
// 301 Redirects from old product slugs
Route::redirect('/product/neon-rewind-set', '/product/neon-spray-moto-denim-set', 301);
Route::redirect('/product/stone-guardian-armor-cloak', '/product/stone-guardian-armor-set', 301);
Route::redirect('/product/boxy-cyber-matrix-shirt', '/product/boxy-cyber-jersey-tee', 301);
Route::redirect('/product/acid-wash-cargo-denim', '/product/camo-tactical-cargo-set', 301);
Route::redirect('/product/dark-glitch-knitted-sweater', '/product/dirty-acid-wash-denim-set', 301);

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/collection/{name}', [ProductController::class, 'collection'])->name('products.collection');

// Cart & Slide-Over Drawer Endpoints
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'store'])->name('cart.add');
Route::put('/cart/item/{itemId}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/item/{itemId}', [CartController::class, 'destroy'])->name('cart.remove');

// 2-Step Checkout
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// Payment (ABA KHQR & Webhooks)
Route::get('/payment/{orderNumber}', [PaymentController::class, 'show'])->name('payment.show');
Route::get('/payment/{orderNumber}/status', [PaymentController::class, 'status'])->name('payment.status');
Route::post('/payment/{orderNumber}/simulate', [PaymentController::class, 'simulateSuccess'])->name('payment.simulate');
Route::post('/payment/{orderNumber}/retry', [PaymentController::class, 'retry'])->name('payment.retry');

// Orders & Order Tracking
Route::match(['get', 'post'], '/track-order', [OrderController::class, 'lookup'])->middleware('throttle:15,1')->name('orders.lookup');
Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/orders/{orderNumber}/success', [OrderController::class, 'show'])->name('orders.success');
Route::get('/orders/{orderNumber}/track', [OrderController::class, 'track'])->name('orders.track');
Route::post('/orders/{orderNumber}/convert-guest', [OrderController::class, 'convertGuest'])->name('orders.convert-guest');

// Authenticated Customer Area
Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::post('/account/address', [AccountController::class, 'storeAddress'])->name('account.address.store');
    Route::delete('/account/address/{address}', [AccountController::class, 'destroyAddress'])->name('account.address.destroy');

    Route::get('/my-orders', [OrderController::class, 'index'])->name('orders.index');

    // Default Breeze Profile redirect to Account Hub
    Route::get('/dashboard', function () {
        return redirect()->route('account.index');
    })->name('dashboard');

    // Profile Management (Standard & Account Hub)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Operations (Orders, Payments, Shipments, Dashboard, Reports)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // ==========================================
    // SALES & FULFILLMENT
    // ==========================================
    // Orders
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}/processing', [AdminOrderController::class, 'processing'])->name('orders.processing');
    Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');

    // Invoices & Receipts
    Route::get('/invoices', [AdminOrderController::class, 'invoices'])->name('invoices.index');
    Route::get('/invoices/{order}', [AdminOrderController::class, 'invoiceShow'])->name('invoices.show');

    // Payments
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/mark-paid', [AdminPaymentController::class, 'markPaid'])->name('payments.mark-paid');
    Route::post('/payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->name('payments.refund');

    // Shipments
    Route::get('/shipments', [AdminShipmentController::class, 'index'])->name('shipments.index');
    Route::put('/shipments/{order}', [AdminShipmentController::class, 'update'])->name('shipments.update');

    // ==========================================
    // CATALOG & INVENTORY
    // ==========================================
    // Products
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::post('/products/{product}/toggle-active', [AdminProductController::class, 'toggleActive'])->name('products.toggle-active');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{product}/restore', [AdminProductController::class, 'restore'])->name('products.restore');

    // Variants (managed inside product edit page)
    Route::post('/products/{product}/variants', [AdminProductVariantController::class, 'store'])->name('variants.store');
    Route::put('/variants/{variant}', [AdminProductVariantController::class, 'update'])->name('variants.update');
    Route::delete('/variants/{variant}', [AdminProductVariantController::class, 'destroy'])->name('variants.destroy');
    Route::post('/variants/{variant}/restore', [AdminProductVariantController::class, 'restore'])->name('variants.restore');

    // Images (managed inside product edit page)
    Route::post('/products/{product}/images', [AdminProductImageController::class, 'store'])->name('images.store');
    Route::put('/images/{image}', [AdminProductImageController::class, 'update'])->name('images.update');
    Route::post('/images/{image}/set-primary', [AdminProductImageController::class, 'setPrimary'])->name('images.set-primary');
    Route::delete('/images/{image}', [AdminProductImageController::class, 'destroy'])->name('images.destroy');

    // Inventory
    Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/{variant}', [AdminInventoryController::class, 'show'])->name('inventory.show');
    Route::post('/inventory/{variant}/adjust', [AdminInventoryController::class, 'adjust'])->name('inventory.adjust');

    // ==========================================
    // CUSTOMERS & RELATIONSHIPS
    // ==========================================
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{user}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{user}/suspend', [AdminCustomerController::class, 'suspend'])->name('customers.suspend');
    Route::post('/customers/{user}/activate', [AdminCustomerController::class, 'activate'])->name('customers.activate');

    // ==========================================
    // SYSTEM: AUDIT & REPORTS
    // ==========================================
    // Audit Logs (Read-only)
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/{log}', [AdminAuditLogController::class, 'show'])->name('audit-logs.show');

    // Reports (Read-only live aggregations)
    Route::get('/reports', function () {
        return redirect()->route('admin.reports.sales');
    })->name('reports.index');
    Route::get('/reports/sales', [AdminReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/products', [AdminReportController::class, 'products'])->name('reports.products');
    Route::get('/reports/regions', [AdminReportController::class, 'regions'])->name('reports.regions');
    Route::get('/reports/customers', [AdminReportController::class, 'customers'])->name('reports.customers');
});

require __DIR__ . '/auth.php';
