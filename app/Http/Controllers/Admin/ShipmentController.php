<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = Order::with(['shipment', 'payment', 'items.variant.product'])->latest();

        if ($status) {
            $query->whereHas('shipment', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        $orders = $query->paginate(15);

        return view('admin.shipments.index', compact('orders', 'status'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'tracking_number' => 'nullable|string|max:50',
            'status' => 'required|in:order_confirmed,processing,shipped,out_for_delivery,delivered',
        ]);

        $shipment = $order->shipment ?: \App\Models\Shipment::firstOrCreate(
            ['order_id' => $order->id],
            ['carrier' => 'J&T Express', 'status' => 'order_confirmed']
        );

        if (!empty($validated['tracking_number'])) {
            $shipment->carrier = 'J&T Express';
            $shipment->tracking_number = trim($validated['tracking_number']);
            $shipment->save();
        }

        $this->shipmentService->updateStatus($shipment, $validated['status']);

        return back()->with('success', "Order {$order->order_number} shipment updated successfully.");
    }
}
