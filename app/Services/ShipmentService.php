<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;
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

        $shipment = $order->shipment ?: new Shipment(['order_id' => $order->id]);
        $shipment->carrier = $carrier;
        $shipment->tracking_number = $trackingNumber;

        // If status was still confirmed/processing, advance it to shipped
        if (in_array($shipment->status, [Shipment::STATUS_ORDER_CONFIRMED, Shipment::STATUS_PROCESSING])) {
            $shipment->status = Shipment::STATUS_SHIPPED;
            $shipment->shipped_at = now();
            $order->update(['order_status' => 'shipped']);
        }

        $shipment->save();

        return $shipment;
    }

    /**
     * Update the Hoyos internal fulfillment status.
     * Allowed: order_confirmed, processing, shipped, out_for_delivery, delivered
     */
    public function updateStatus(Shipment $shipment, string $newStatus): Shipment
    {
        $allowed = [
            Shipment::STATUS_ORDER_CONFIRMED,
            Shipment::STATUS_PROCESSING,
            Shipment::STATUS_SHIPPED,
            Shipment::STATUS_OUT_FOR_DELIVERY,
            Shipment::STATUS_DELIVERED,
        ];

        if (!in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException("Invalid internal shipping status: {$newStatus}");
        }

        $shipment->status = $newStatus;

        if ($newStatus === Shipment::STATUS_SHIPPED && !$shipment->shipped_at) {
            $shipment->shipped_at = now();
            $shipment->order->update(['order_status' => 'shipped']);
        }

        if ($newStatus === Shipment::STATUS_DELIVERED && !$shipment->delivered_at) {
            $shipment->delivered_at = now();
            $shipment->order->update(['order_status' => 'delivered']);
        }

        $shipment->save();

        return $shipment;
    }
}
