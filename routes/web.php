<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AccountController;
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

// Orders & Order Tracking
Route::match(['get', 'post'], '/track-order', [OrderController::class, 'lookup'])->name('orders.lookup');
Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
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

// Admin Operations (Shipments & J&T Tracking Code Entry)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/shipments', [AdminShipmentController::class, 'index'])->name('shipments.index');
    Route::put('/shipments/{order}', [AdminShipmentController::class, 'update'])->name('shipments.update');
});

require __DIR__ . '/auth.php';
