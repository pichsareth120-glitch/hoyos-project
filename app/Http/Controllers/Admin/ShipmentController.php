<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\AdminAuditLogger;
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
        $query = Order::readyForFulfillment()
            ->with(['shipment', 'latestPayment', 'items.variant.product'])
            ->latest();

        if ($status === 'in_transit') {
            $query->whereHas('shipment', function ($q) {
                $q->whereIn('status', ['shipped', 'out_for_delivery']);
            });
        } elseif ($status) {
            $query->whereHas('shipment', function ($q) use ($status) {
                $q->where('status', $status);
            });
        } else {
            // Exclude cancelled orders from the default queue
            $query->where('order_status', '!=', 'cancelled')
                ->whereDoesntHave('shipment', function ($q) {
                    $q->where('status', Shipment::STATUS_CANCELLED);
                });
        }

        $orders = $query->paginate(15)->withQueryString();

        // Single grouped count query for real KPI counts (zero N+1)
        $shipmentCounts = Order::readyForFulfillment()
            ->where('order_status', '!=', 'cancelled')
            ->leftJoin('shipments', 'orders.id', '=', 'shipments.order_id')
            ->selectRaw("
                COUNT(DISTINCT orders.id) as total_shipments,
                COUNT(DISTINCT CASE WHEN COALESCE(shipments.status, 'order_confirmed') = 'order_confirmed' THEN orders.id END) as confirmed,
                COUNT(DISTINCT CASE WHEN shipments.status = 'processing' THEN orders.id END) as processing,
                COUNT(DISTINCT CASE WHEN shipments.status IN ('shipped', 'out_for_delivery') THEN orders.id END) as in_transit,
                COUNT(DISTINCT CASE WHEN shipments.status = 'delivered' THEN orders.id END) as delivered
            ")
            ->first();

        return view('admin.shipments.index', compact('orders', 'status', 'shipmentCounts'));
    }

    public function update(Request $request, Order $order)
    {
        // Reject updates to shipments whose order is 'cancelled'
        if ($order->order_status === 'cancelled') {
            return back()->with('error', "Cannot update shipment for cancelled order #{$order->order_number}.");
        }

        $validated = $request->validate([
            'tracking_number' => 'nullable|string|max:50',
            'status' => 'required|in:order_confirmed,processing,shipped,out_for_delivery,delivered,cancelled',
        ]);

        $shipment = $order->shipment ?: \App\Models\Shipment::firstOrCreate(
            ['order_id' => $order->id],
            ['carrier' => 'J&T Express', 'status' => 'order_confirmed']
        );

        if ($shipment->status === Shipment::STATUS_CANCELLED) {
            return back()->with('error', "Cannot update a cancelled shipment for order #{$order->order_number}.");
        }

        $newTracking = isset($validated['tracking_number']) && trim($validated['tracking_number']) !== ''
            ? trim($validated['tracking_number'])
            : null;
        $oldTracking = $shipment->tracking_number;

        if ($newTracking !== $oldTracking) {
            $shipment->carrier = 'J&T Express';
            $shipment->tracking_number = $newTracking;
            $shipment->save();

            AdminAuditLogger::log(
                'shipments',
                'update_tracking',
                'shipments',
                $shipment->id,
                ['tracking_number' => $oldTracking],
                ['tracking_number' => $newTracking]
            );
        }

        try {
            $this->shipmentService->updateStatus($shipment, $validated['status']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Order {$order->order_number} shipment updated successfully.");
    }
}
