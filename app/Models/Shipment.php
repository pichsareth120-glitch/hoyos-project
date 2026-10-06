<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'carrier',
        'tracking_number',
        'status',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public const STATUS_ORDER_CONFIRMED = 'order_confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getExternalTrackingUrlAttribute(): ?string
    {
        if (empty($this->tracking_number)) {
            return null;
        }

        // Official J&T Express Cambodia tracking URL format
        return 'https://www.jtexpress.com.kh/tracking?billcode=' . urlencode($this->tracking_number);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ORDER_CONFIRMED => 'Order Confirmed',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
            self::STATUS_DELIVERED => 'Delivered',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
