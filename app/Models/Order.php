<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'guest_token',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'province',
        'delivery_notes',
        'subtotal',
        'shipping_fee',
        'total_amount',
        'order_status',
        'payment_method',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'HOYOS-' . strtoupper(Str::random(6));
            }
            if (empty($order->guest_token) && empty($order->user_id)) {
                $order->guest_token = Str::random(40);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class)
            ->orderByRaw("CASE 
                WHEN LOWER(variant_size) = 'xs' THEN 1
                WHEN LOWER(variant_size) = 's' THEN 2
                WHEN LOWER(variant_size) = 'm' THEN 3
                WHEN LOWER(variant_size) = 'l' THEN 4
                WHEN LOWER(variant_size) = 'xl' THEN 5
                WHEN LOWER(variant_size) = 'xxl' THEN 6
                WHEN LOWER(variant_size) = 'one size' THEN 7
                ELSE 99
            END ASC");
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isCod(): bool
    {
        return $this->payment_method === 'cod';
    }

    public function getShippingCostAttribute(): float
    {
        return (float) ($this->shipping_fee ?? 0.00);
    }

    public function canAccess(?User $user, ?string $guestToken = null): bool
    {
        if ($user && $this->user_id && (int) $this->user_id === (int) $user->id) {
            return true;
        }

        if ($user && $user->isAdmin()) {
            return true;
        }

        if ($guestToken && hash_equals((string) $this->guest_token, (string) $guestToken)) {
            return true;
        }

        return false;
    }
}
