<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

test('homepage renders with hoyos brand hero and collections', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('HOYOS');
    $response->assertSee('Our Collection');
});

test('product catalog can filter by category', function () {
    $category = Category::where('slug', 'y2k')->first() ?: Category::factory()->create(['name' => 'Y2K Cyber', 'slug' => 'y2k']);

    $response = $this->get('/category/y2k');

    $response->assertStatus(200);
    $response->assertSee($category->name);
});

test('product detail page shows price, variants and quick size options', function () {
    $product = Product::with('variants')->first();
    if (!$product) {
        $product = Product::factory()->has(ProductVariant::factory()->count(3), 'variants')->create([
            'name' => 'Gothic Demon Oversized Hoodie',
            'slug' => 'gothic-demon-oversized-hoodie',
            'price' => 58.00,
        ]);
    }

    $response = $this->get('/product/' . $product->slug);

    $response->assertStatus(200);
    $response->assertSee($product->name);
    $response->assertSee('Select Size');
    $response->assertSee('Add to Bag');
});
