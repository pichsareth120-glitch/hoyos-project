<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderAction;
use App\Models\Address;
use App\Services\CartService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CreateOrderAction $createOrderAction
    ) {}

    public function index()
    {
        $cart = $this->cartService->getCart();

        if ($cart->items->isEmpty()) {
            return redirect()->route('home')->with('info', 'Your shopping bag is empty.');
        }

        $user = Auth::user();
        $savedAddresses = $user ? $user->addresses : collect();
        $defaultAddress = $user ? $user->defaultAddress : null;

        $provinces = [
            'Phnom Penh',
            'Kandal',
            'Siem Reap',
            'Battambang',
            'Sihanoukville',
            'Kampot',
            'Banteay Meanchey',
            'Kampong Cham',
            'Kampong Chhnang',
            'Kampong Speu',
            'Kampong Thom',
            'Koh Kong',
            'Kratie',
            'Mondulkiri',
            'Oddar Meanchey',
            'Pailin',
            'Preah Vihear',
            'Prey Veng',
            'Pursat',
            'Ratanakiri',
            'Stung Treng',
            'Svay Rieng',
            'Takeo',
            'Tbong Khmum',
            'Kep',
        ];

        return view('checkout.index', compact('cart', 'user', 'savedAddresses', 'defaultAddress', 'provinces'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|min:2|max:100',
            'customer_phone' => ['required', 'string', 'regex:/^(?:\+855|0)[1-9]\d{7,8}$/'],
            'customer_email' => 'nullable|email|max:150',
            'province' => 'required|string|max:100',
            'shipping_address' => 'required|string|min:5|max:255',
            'delivery_notes' => 'nullable|string|max:500',
            'payment_method' => 'required|in:aba_khqr,cod',
            'save_address' => 'nullable|boolean',
        ]);

        $cart = $this->cartService->getCart();

        if ($cart->items->isEmpty()) {
            return redirect()->route('home')->with('error', 'Your shopping bag is empty.');
        }

        $user = Auth::user();

        // Optionally save address to user's profile if requested
        if ($user && !empty($validated['save_address'])) {
            Address::create([
                'user_id' => $user->id,
                'recipient_name' => $validated['customer_name'],
                'recipient_phone' => $validated['customer_phone'],
                'province' => $validated['province'],
                'address_line' => $validated['shipping_address'],
                'notes' => $validated['delivery_notes'] ?? null,
                'is_default' => $user->addresses()->count() === 0,
            ]);
        }

        try {
            $order = $this->createOrderAction->execute($cart, $validated, $user);

            // If user checked out as guest, store the guest token in session
            if (!$user && $order->guest_token) {
                session(['guest_order_token_' . $order->order_number => $order->guest_token]);
            }

            if ($order->payment_method === 'aba_khqr') {
                return redirect()->route('payment.show', [
                    'orderNumber' => $order->order_number,
                    'token' => $order->guest_token,
                ]);
            }

            // Cash on Delivery goes straight to order confirmation
            return redirect()->route('orders.show', [
                'orderNumber' => $order->order_number,
                'token' => $order->guest_token,
            ])->with('success', 'Order placed successfully! We will prepare your shipment via J&T Express.');

        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
