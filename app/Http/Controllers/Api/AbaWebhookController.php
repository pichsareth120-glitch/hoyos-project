<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\AdminAuditLogger;
use App\Services\OrderStatusRecorder;
use App\Services\Payment\AbaPaywayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbaWebhookController extends Controller
{
    public function __construct(
        protected AbaPaywayService $paywayService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        // In demo mode, external webhooks are disabled; external callbacks cannot be accepted as proof of payment
        if (config('hoyos.demo_mode', false)) {
            return response()->json([
                'status' => 1,
                'message' => 'Webhooks are disabled in demo mode. Please use simulated payment approval.',
            ], 403);
        }

        // 1. Verify signature
        if (!$this->paywayService->verifyWebhookSignature($request)) {
            return response()->json([
                'status' => 1,
                'message' => 'Invalid signature',
            ], 403);
        }

        $tranId = $request->input('tran_id') ?? $request->input('order_number');
        $status = $request->input('status');

        if (!$tranId) {
            return response()->json([
                'status' => 1,
                'message' => 'Missing transaction identifier',
            ], 422);
        }

        // 2. Database transaction with row lock
        return DB::transaction(function () use ($tranId, $status, $request) {
            $order = Order::where('order_number', $tranId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                // Try finding by payment transaction_id
                $payment = Payment::where('transaction_id', $tranId)->first();
                if ($payment) {
                    $order = Order::where('id', $payment->order_id)
                        ->lockForUpdate()
                        ->first();
                }
            }

            if (!$order) {
                return response()->json([
                    'status' => 1,
                    'message' => 'Order not found',
                ], 404);
            }

            // Check if already paid (idempotent handling)
            if ($order->isPaid()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Already processed',
                ]);
            }

            // When status == 0 (ABA Success)
            if ((string) $status === '0' || (string) $status === '00') {
                $oldPaymentStatus = $order->payment_status;
                $oldOrderStatus = $order->order_status;

                $order->payment_status = 'paid';
                if ($order->order_status === 'pending') {
                    $order->order_status = 'confirmed';
                }
                $order->save();

                // Update payment record
                $payment = $order->latestPayment;
                if ($payment) {
                    $payment->status = Payment::STATUS_PAID;
                    $payment->paid_at = now();
                    $payload = $payment->payload ?: [];
                    $payload['webhook_received_at'] = now()->toIso8601String();
                    $payload['webhook_data'] = $request->all();
                    $payment->payload = $payload;
                    $payment->save();
                }

                // Record order history transition if status changed
                if ($oldOrderStatus !== $order->order_status) {
                    OrderStatusRecorder::record(
                        $order,
                        $oldOrderStatus,
                        $order->order_status,
                        null,
                        "Payment confirmed via ABA PayWay Webhook (tran_id: {$tranId})"
                    );
                }

                // Record audit log entry
                AdminAuditLogger::log(
                    module: 'payments',
                    action: 'confirm',
                    targetTable: 'orders',
                    targetId: $order->id,
                    oldValues: [
                        'payment_status' => $oldPaymentStatus,
                        'order_status' => $oldOrderStatus,
                    ],
                    newValues: [
                        'payment_status' => 'paid',
                        'order_status' => $order->order_status,
                        'transaction_id' => $tranId,
                    ],
                    adminId: null,
                    forceNullActor: true
                );

                return response()->json([
                    'status' => 0,
                    'message' => 'Success',
                ]);
            }

            return response()->json([
                'status' => 0,
                'message' => 'Status acknowledged: ' . $status,
            ]);
        });
    }
}
