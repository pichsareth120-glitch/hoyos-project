<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    public const DEFAULT_LOW_STOCK_THRESHOLD = 5;

    public const SIZE_ORDER = [
        'XS' => 1,
        'S' => 2,
        'M' => 3,
        'L' => 4,
        'XL' => 5,
        'XXL' => 6,
        'One size' => 7,
    ];

    public static function lowStockThreshold(): int
    {
        return (int) config('hoyos.low_stock_threshold', self::DEFAULT_LOW_STOCK_THRESHOLD);
    }

    public function scopeLowStock($query, ?int $threshold = null)
    {
        $threshold = $threshold ?? self::lowStockThreshold();

        return $query->where('stock_quantity', '<=', $threshold)
            ->where('stock_quantity', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }

    public static function sizeOrderIndex(?string $size): int
    {
        if (!$size) {
            return 99;
        }

        $trimmed = trim($size);
        foreach (self::SIZE_ORDER as $key => $index) {
            if (strcasecmp($trimmed, $key) === 0) {
                return $index;
            }
        }

        return 99;
    }

    protected $fillable = [
        'product_id',
        'size',
        'sku',
        'stock_quantity',
        'price_adjustment',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'integer',
            'price_adjustment' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) $this->product->price + (float) $this->price_adjustment;
    }
}
