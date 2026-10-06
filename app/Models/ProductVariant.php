<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

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

    public function getEffectivePriceAttribute(): float
    {
        return (float) $this->product->price + (float) $this->price_adjustment;
    }
}
