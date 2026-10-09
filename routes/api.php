<?php

use App\Http\Controllers\Api\AbaWebhookController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/aba', [AbaWebhookController::class, 'handle'])->name('api.webhooks.aba');
Route::get('/orders/{orderNumber}/payment-status', [PaymentController::class, 'status'])->name('api.orders.payment-status');
