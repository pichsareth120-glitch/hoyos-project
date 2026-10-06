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
        $order = Order::with(['payment', 'items.variant.product'])->where('order_number', $orderNumber)->firstOrFail();

        if (!$order->canAccess(Auth::user(), $token)) {
            abort(403, 'Unauthorized access to this order payment.');
        }

        if ($order->isPaid()) {
            return redirect()->route('orders.show', ['orderNumber' => $orderNumber, 'token' => $token]);
        }

        $payment = $this->abaPaymentService->createKhqrPayment($order);
        $this->abaPaymentService->checkExpiry($payment);

        return view('payment.khqr', compact('order', 'payment', 'token'));
    }

    public function status(Request $request, string $orderNumber)
    {
        $token = $request->query('token') ?: session('guest_order_token_' . $orderNumber);
        $order = Order::with('payment')->where('order_number', $orderNumber)->firstOrFail();

        if (!$order->canAccess(Auth::user(), $token)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $payment = $order->payment;
        if ($payment) {
            $this->abaPaymentService->checkExpiry($payment);
        }

        return response()->json([
            'is_paid' => $order->isPaid(),
            'status' => $payment ? $payment->status : 'pending',
            'is_expired' => $payment ? $payment->isExpired() : false,
            'redirect_url' => route('orders.show', ['orderNumber' => $orderNumber, 'token' => $token]),
        ]);
    }

    public function simulateSuccess(Request $request, string $orderNumber)
    {
        $token = $request->query('token') ?: session('guest_order_token_' . $orderNumber);
        $order = Order::with('payment')->where('order_number', $orderNumber)->firstOrFail();

        if (!$order->canAccess(Auth::user(), $token)) {
            abort(403, 'Unauthorized access.');
        }

        $payment = $order->payment;
        if ($payment) {
            $this->abaPaymentService->markAsPaid($payment, 'DEV-SIMULATION-' . strtoupper(str()->random(6)));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('orders.show', ['orderNumber' => $orderNumber, 'token' => $token]),
            ]);
        }

        return redirect()->route('orders.show', ['orderNumber' => $orderNumber, 'token' => $token])
            ->with('success', 'Payment verified successfully!');
    }
}
