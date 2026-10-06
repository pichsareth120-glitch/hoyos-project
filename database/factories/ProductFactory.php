<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true) . ' Graphic Hoodie';
        return [
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 25, 85),
            'collection_name' => fake()->randomElement(['Cyber Rave', 'Gothic Abyss', 'Demon Y2K']),
            'is_active' => true,
            'is_featured' => true,
            'rating' => 4.9,
            'review_count' => 12,
        ];
    }
}
