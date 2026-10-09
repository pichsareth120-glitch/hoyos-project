<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelOrderAction
{
    public function __construct(
        protected RestoreStockAction $restoreStockAction
    ) {}

    /**
     * Cancel an active order, update fulfillment & payment states, and restore reserved stock.
     *
     * @param Order $order
     * @param User|null $admin
     * @param string $reason
     * @param string $stockType 'order_cancelled' or 'payment_expired'
     * @return Order
     * @throws DomainException
     */
    public function execute(
        Order $order,
        ?User $admin = null,
        string $reason = 'Order cancelled',
        string $stockType = 'order_cancelled'
    ): Order {
        if (!in_array($order->order_status, ['confirmed', 'processing'], true)) {
            throw new DomainException("Cannot cancel order with status '{$order->order_status}'. Only confirmed or processing orders can be cancelled.");
        }

        return DB::transaction(function () use ($order, $admin, $reason, $stockType) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if (!in_array($lockedOrder->order_status, ['confirmed', 'processing'], true)) {
                throw new DomainException("Cannot cancel order with status '{$lockedOrder->order_status}'. Only confirmed or processing orders can be cancelled.");
            }

            $oldStatus = $lockedOrder->order_status;
            $lockedOrder->order_status = 'cancelled';

            // Payment handling:
            // COD pending => payments.status and orders.payment_status 'cancelled'.
            // Unpaid KHQR => 'expired'.
            // Already 'paid' => leave unchanged (an admin records the refund later).
            if ($lockedOrder->isCod() && $lockedOrder->payment_status === 'pending') {
                $lockedOrder->payment_status = Payment::STATUS_CANCELLED;
                $lockedOrder->payments()->where('status', 'pending')->update(['status' => Payment::STATUS_CANCELLED]);
            } elseif ($lockedOrder->payment_method === 'aba_khqr' && $lockedOrder->payment_status !== 'paid') {
                $lockedOrder->payment_status = Payment::STATUS_EXPIRED;
                $lockedOrder->payments()->where('status', 'pending')->update(['status' => Payment::STATUS_EXPIRED]);
            }

            $lockedOrder->save();

            // Shipment status => 'cancelled' and audit row (handles orders with no shipment)
            if ($shipment = $lockedOrder->shipment) {
                $oldShipmentStatus = $shipment->status;
                if ($oldShipmentStatus !== Shipment::STATUS_CANCELLED) {
                    $shipment->status = Shipment::STATUS_CANCELLED;
                    $shipment->save();

                    \App\Services\AdminAuditLogger::log(
                        'shipments',
                        'cancel',
                        'shipments',
                        $shipment->id,
                        ['status' => $oldShipmentStatus],
                        ['status' => Shipment::STATUS_CANCELLED, 'reason' => $reason],
                        $admin?->id,
                        forceNullActor: ($admin === null)
                    );
                }
            }

            // Write order_status_history row
            \App\Services\OrderStatusRecorder::record(
                $lockedOrder,
                $oldStatus,
                'cancelled',
                $admin?->id,
                $reason
            );

            // Write admin audit log (null actor if automated by system)
            \App\Services\AdminAuditLogger::log(
                'orders',
                'cancel',
                'orders',
                $lockedOrder->id,
                ['order_status' => $oldStatus],
                ['order_status' => 'cancelled', 'reason' => $reason],
                $admin?->id,
                forceNullActor: ($admin === null)
            );

            // Restore reserved stock
            $this->restoreStockAction->execute($lockedOrder, $stockType, $admin, $reason);

            return $lockedOrder;
        });
    }

    public function __invoke(
        Order $order,
        ?User $admin = null,
        string $reason = 'Order cancelled',
        string $stockType = 'order_cancelled'
    ): Order {
        return $this->execute($order, $admin, $reason, $stockType);
    }
}
