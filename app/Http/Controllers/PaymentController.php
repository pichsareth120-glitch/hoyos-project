<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AbaPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        protected AbaPaymentService $abaPaymentService
    ) {}

    public function show(Request $request, string $orderNumber)
    {
        $token = $request->query('token') ?: session('guest_order_token_' . $orderNumber);
        $order = Order::with(['latestPayment', 'items.variant.product'])->where('order_number', $orderNumber)->firstOrFail();

        if (!$order->canAccess(Auth::user(), $token)) {
            abort(403, 'Unauthorized access to this order payment.');
        }

        if ($order->isPaid()) {
            return redirect()->route('orders.show', ['orderNumber' => $orderNumber, 'token' => $token]);
        }

        if ($order->order_status === 'cancelled') {
            $payment = $order->latestPayment;
            return view('payment.khqr', compact('order', 'payment', 'token'));
        }

        $payment = $this->abaPaymentService->createKhqrPayment($order);
        $this->abaPaymentService->checkExpiry($payment);

        return view('payment.khqr', compact('order', 'payment', 'token'));
    }

    public function status(Request $request, string $orderNumber)
    {
        $token = $request->query('token') ?: session('guest_order_token_' . $orderNumber);
        $order = Order::with('latestPayment')->where('order_number', $orderNumber)->firstOrFail();

        if (!$order->canAccess(Auth::user(), $token)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $payment = $order->latestPayment;
        if ($payment) {
            $this->abaPaymentService->checkExpiry($payment);
        }

        return response()->json([
            'is_paid' => $order->isPaid(),
            'is_cancelled' => $order->order_status === 'cancelled',
            'status' => $payment ? $payment->status : 'pending',
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
            'is_expired' => $payment ? $payment->isExpired() : false,
            'redirect_url' => route('orders.show', ['orderNumber' => $orderNumber, 'token' => $token]),
        ]);
    }

    public function retry(Request $request, string $orderNumber)
    {
        $token = $request->query('token') ?: $request->input('token') ?: session('guest_order_token_' . $orderNumber);
        $order = Order::with('latestPayment')->where('order_number', $orderNumber)->firstOrFail();

        if (!$order->canAccess(Auth::user(), $token)) {
            abort(403, 'Unauthorized access to this order payment.');
        }

        $latestPayment = $order->latestPayment;
        $graceMinutes = (int) config('hoyos.payment_grace_minutes', 15);

        $isExpired = $latestPayment && ($latestPayment->status === 'expired' || $latestPayment->isExpired());
        $isInsideGrace = false;

        if ($latestPayment && $latestPayment->expires_at) {
            $cutoff = $latestPayment->expires_at->copy()->addMinutes($graceMinutes);
            $isInsideGrace = now()->lessThanOrEqualTo($cutoff);
        }

        if (
            $order->order_status !== 'confirmed' ||
            $order->payment_method !== 'aba_khqr' ||
            !$isExpired ||
            !$isInsideGrace
        ) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Payment cannot be retried.'], 422);
            }

            return redirect()->route('payment.show', ['orderNumber' => $orderNumber, 'token' => $token])
                ->with('error', 'Payment retry window has closed or order has been cancelled.');
        }

        $this->abaPaymentService->createKhqrPayment($order);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('payment.show', ['orderNumber' => $orderNumber, 'token' => $token]),
            ]);
        }

        return redirect()->route('payment.show', ['orderNumber' => $orderNumber, 'token' => $token])
            ->with('success', 'New QR code generated successfully.');
    }

    public function simulateSuccess(Request $request, string $orderNumber)
    {
        // Simulation is an explicitly demo-only workflow.
        if (!config('hoyos.allow_payment_simulation', false)
            || (!config('hoyos.demo_mode', false) && !app()->environment(['local', 'testing']))) {
            abort(404, 'Payment simulation is disabled.');
        }

        $token = $request->query('token') ?: session('guest_order_token_' . $orderNumber);
        $order = Order::with('latestPayment')->where('order_number', $orderNumber)->firstOrFail();

        if (!$order->canAccess(Auth::user(), $token)) {
            abort(403, 'Unauthorized access.');
        }

        if ($order->order_status === 'cancelled') {
            abort(422, 'Cannot simulate payment for a cancelled order.');
        }

        $payment = $order->latestPayment;
        if ($payment) {
            if ($payment->isExpired()) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'Payment has expired. Please regenerate a new QR code.'], 422);
                }
                abort(422, 'Payment has expired. Please regenerate a new QR code.');
            }

            $prefix = config('hoyos.demo_mode', false) ? 'DEMO-SIMULATION-' : 'DEV-SIMULATION-';
            $this->abaPaymentService->markAsPaid($payment, $prefix . strtoupper(str()->random(6)));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('orders.show', ['orderNumber' => $orderNumber, 'token' => $token]),
            ]);
        }

        $successMsg = config('hoyos.demo_mode', false)
            ? 'Simulated demo payment verified successfully! (No real financial charge)'
            : 'Payment verified successfully!';

        return redirect()->route('orders.show', ['orderNumber' => $orderNumber, 'token' => $token])
            ->with('success', $successMsg);
    }
}
