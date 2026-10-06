<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'size_guide',
        'price',
        'compare_at_price',
        'collection_name',
        'rating',
        'review_count',
        'is_featured',
        'is_new_arrival',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'rating' => 'decimal:2',
            'review_count' => 'integer',
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('display_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function scopeWithRealImage($query)
    {
        return $query->whereHas('images', function ($q) {
            $q->where('image_path', 'not like', '%placeholder%')
              ->whereNotNull('image_path')
              ->where('image_path', '!=', '');
        });
    }

    public function hasRealImage(): bool
    {
        return $this->images->contains(function ($img) {
            return !empty($img->image_path) && !str_contains($img->image_path, 'placeholder');
        });
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 0);
    }
}
