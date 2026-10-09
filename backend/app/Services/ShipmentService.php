<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShipmentService
{
    /**
     * Assign or update a J&T Express tracking number for an order.
     */
    public function assignTrackingNumber(Order $order, string $trackingNumber, string $carrier = 'J&T Express'): Shipment
    {
        $trackingNumber = trim($trackingNumber);

        if (empty($trackingNumber)) {
            throw new InvalidArgumentException('Tracking number cannot be empty.');
        }

        return DB::transaction(function () use ($order, $trackingNumber, $carrier) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            $shipment = $lockedOrder->shipment ?: new Shipment(['order_id' => $lockedOrder->id]);
            $oldShipmentStatus = $shipment->status ?? Shipment::STATUS_ORDER_CONFIRMED;
            $shipment->carrier = $carrier;
            $shipment->tracking_number = $trackingNumber;

            // If status was still confirmed/processing, advance it to shipped
            if (in_array($shipment->status, [Shipment::STATUS_ORDER_CONFIRMED, Shipment::STATUS_PROCESSING])) {
                if (!$lockedOrder->isCod() && !$lockedOrder->isPaid()) {
                    throw new InvalidArgumentException("Shipment cannot move to 'shipped': Order #{$lockedOrder->order_number} ({$lockedOrder->payment_method}) is not paid.");
                }

                $shipment->status = Shipment::STATUS_SHIPPED;
                $shipment->shipped_at = now();

                $oldOrderStatus = $lockedOrder->order_status;
                if ($oldOrderStatus !== 'shipped') {
                    $lockedOrder->update(['order_status' => 'shipped']);
                    OrderStatusRecorder::record($lockedOrder, $oldOrderStatus, 'shipped', null, "Tracking number {$trackingNumber} assigned; order marked shipped");
                    AdminAuditLogger::log('shipments', 'update_status', 'orders', $lockedOrder->id, ['order_status' => $oldOrderStatus], ['order_status' => 'shipped']);
                }
            }

            $shipment->save();
            AdminAuditLogger::log('shipments', 'assign_tracking', 'shipments', $shipment->id, ['status' => $oldShipmentStatus], ['status' => $shipment->status, 'tracking_number' => $trackingNumber]);

            return $shipment;
        });
    }

    /**
     * Update the Hoyos internal fulfillment status.
     * Allowed: order_confirmed, processing, shipped, out_for_delivery, delivered, cancelled
     */
    public function updateStatus(Shipment $shipment, string $newStatus): Shipment
    {
        $allowed = [
            Shipment::STATUS_ORDER_CONFIRMED,
            Shipment::STATUS_PROCESSING,
            Shipment::STATUS_SHIPPED,
            Shipment::STATUS_OUT_FOR_DELIVERY,
            Shipment::STATUS_DELIVERED,
            Shipment::STATUS_CANCELLED,
        ];

        if (!in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException("Invalid internal shipping status: {$newStatus}");
        }

        return DB::transaction(function () use ($shipment, $newStatus) {
            $lockedShipment = Shipment::where('id', $shipment->id)->lockForUpdate()->firstOrFail();
            $order = Order::where('id', $lockedShipment->order_id)->lockForUpdate()->firstOrFail();

            if (in_array($newStatus, [Shipment::STATUS_SHIPPED, Shipment::STATUS_OUT_FOR_DELIVERY, Shipment::STATUS_DELIVERED], true)) {
                if (!$order->isCod() && !$order->isPaid()) {
                    throw new InvalidArgumentException("Shipment cannot move to '{$newStatus}': Order #{$order->order_number} ({$order->payment_method}) is not paid.");
                }
            }

            $oldShipmentStatus = $lockedShipment->status;
            $statusChanged = $oldShipmentStatus !== $newStatus;
            $lockedShipment->status = $newStatus;

            if ($newStatus === Shipment::STATUS_SHIPPED && !$lockedShipment->shipped_at) {
                $lockedShipment->shipped_at = now();
            }

            if ($statusChanged && $newStatus === Shipment::STATUS_SHIPPED && $order->order_status !== 'shipped') {
                $oldOrderStatus = $order->order_status;
                $order->update(['order_status' => 'shipped']);
                OrderStatusRecorder::record($order, $oldOrderStatus, 'shipped', null, "Shipment moved to shipped");
                AdminAuditLogger::log('shipments', 'update_status', 'orders', $order->id, ['order_status' => $oldOrderStatus], ['order_status' => 'shipped']);
            }

            if ($newStatus === Shipment::STATUS_DELIVERED && !$lockedShipment->delivered_at) {
                $lockedShipment->delivered_at = now();
            }

            if ($statusChanged && $newStatus === Shipment::STATUS_DELIVERED && $order->order_status !== 'delivered') {
                $oldOrderStatus = $order->order_status;
                $order->update(['order_status' => 'delivered']);
                OrderStatusRecorder::record($order, $oldOrderStatus, 'delivered', null, "Shipment delivered");
                AdminAuditLogger::log('shipments', 'update_status', 'orders', $order->id, ['order_status' => $oldOrderStatus], ['order_status' => 'delivered']);
            }

            $lockedShipment->save();

            if ($statusChanged) {
                $action = ($newStatus === Shipment::STATUS_CANCELLED) ? 'cancel' : 'update_status';
                AdminAuditLogger::log('shipments', $action, 'shipments', $lockedShipment->id, ['status' => $oldShipmentStatus], ['status' => $newStatus]);
            }

            return $lockedShipment;
        });
    }
}
