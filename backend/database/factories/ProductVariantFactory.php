<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    protected static int $sizeIndex = 0;

    public function definition(): array
    {
        $sizes = ['M', 'L', 'XL', 'XXL'];
        $size = $sizes[static::$sizeIndex % count($sizes)];
        static::$sizeIndex++;

        return [
            'product_id' => Product::factory(),
            'size' => $size,
            'sku' => 'VAR-' . strtoupper(fake()->unique()->bothify('??#####')),
            'stock_quantity' => 20,
            'price_adjustment' => 0.00,
        ];
    }
}
