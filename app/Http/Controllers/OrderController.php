<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class OrderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('info', 'Please sign in to view your orders.');
        }

        $orders = $user->orders()->with(['items.variant.product.primaryImage', 'shipment', 'payment'])->latest()->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, string $orderNumber)
    {
        $token = $request->query('token') ?: session('guest_order_token_' . $orderNumber);
        $order = Order::with(['items.variant.product.primaryImage', 'shipment', 'payment'])->where('order_number', $orderNumber)->firstOrFail();

        if (!$order->canAccess(Auth::user(), $token)) {
            abort(403, 'Unauthorized access to this order.');
        }

        return view('orders.show', compact('order', 'token'));
    }

    public function track(Request $request, string $orderNumber)
    {
        $token = $request->query('token') ?: session('guest_order_token_' . $orderNumber);
        $order = Order::with(['items.variant.product.primaryImage', 'shipment', 'payment'])->where('order_number', $orderNumber)->firstOrFail();

        if (!$order->canAccess(Auth::user(), $token)) {
            abort(403, 'Unauthorized access to this order tracking.');
        }

        $shipment = $order->shipment;

        return view('orders.track', compact('order', 'shipment', 'token'));
    }

    public function lookup(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'order_number' => 'required|string',
                'customer_phone' => 'required|string',
            ]);

            $order = Order::where('order_number', trim($validated['order_number']))
                ->where('customer_phone', trim($validated['customer_phone']))
                ->first();

            if (!$order) {
                return back()->withInput()->with('error', 'Order not found. Please verify your order number and phone number.');
            }

            // Store guest token in session for authorization
            if ($order->guest_token) {
                session(['guest_order_token_' . $order->order_number => $order->guest_token]);
            }

            return redirect()->route('orders.track', [
                'orderNumber' => $order->order_number,
                'token' => $order->guest_token,
            ]);
        }

        return view('orders.lookup');
    }

    public function convertGuest(Request $request, string $orderNumber)
    {
        $token = $request->query('token') ?: session('guest_order_token_' . $orderNumber);
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if (!$order->canAccess(Auth::user(), $token)) {
            abort(403, 'Unauthorized action.');
        }

        if ($order->user_id) {
            return redirect()->route('orders.show', ['orderNumber' => $orderNumber])
                ->with('info', 'This order is already linked to an account.');
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = $order->customer_email ?: strtolower(str_replace(' ', '.', $order->customer_name)) . '@customer.hoyos';

        // Find or create user
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $order->customer_name,
                'email' => $email,
                'phone' => $order->customer_phone,
                'password' => Hash::make($validated['password']),
            ]);
        }

        // Associate order with newly created account
        $order->update([
            'user_id' => $user->id,
            'guest_token' => null,
        ]);

        Auth::login($user);

        return redirect()->route('orders.show', ['orderNumber' => $orderNumber])
            ->with('success', 'Your account has been created and linked to this order!');
    }
}
