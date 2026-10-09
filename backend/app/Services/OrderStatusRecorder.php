<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\Auth;

class OrderStatusRecorder
{
    /**
     * Record an order status transition into order_status_history.
     *
     * @param Order|int $order Order model or order primary key ID
     * @param string|null $oldStatus Status before transition (null for order creation)
     * @param string $newStatus Status after transition
     * @param int|null $adminId Staff user ID or null for system
     * @param string|null $notes Internal reason/transition remarks
     * @return OrderStatusHistory
     */
    public static function record(
        Order|int $order,
        ?string $oldStatus,
        string $newStatus,
        ?int $adminId = null,
        ?string $notes = null
    ): OrderStatusHistory {
        $orderId = $order instanceof Order ? $order->id : (int) $order;
        $resolvedAdminId = $adminId;

        if ($resolvedAdminId === null && Auth::check()) {
            $user = Auth::user();
            if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                $resolvedAdminId = $user->id;
            }
        }

        return OrderStatusHistory::create([
            'order_id' => $orderId,
            'admin_id' => $resolvedAdminId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
