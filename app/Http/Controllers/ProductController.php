<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, ?string $categorySlug = null)
    {
        $categories = Category::all();
        $activeCategory = null;

        $query = Product::with(['category', 'primaryImage', 'variants'])
            ->withRealImage()
            ->where('is_active', true);

        if ($categorySlug) {
            $activeCategory = Category::where('slug', $categorySlug)->firstOrFail();
            $query->where('category_id', $activeCategory->id);
        }

        $products = $query->paginate(12);

        $recommendations = Product::with(['category', 'primaryImage', 'variants'])
            ->withRealImage()
            ->where('is_active', true)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('products.index', compact('categories', 'activeCategory', 'products', 'recommendations'));
    }

    public function show(string $slug)
    {
        $redirects = [
            'neon-rewind-set' => 'neon-spray-moto-denim-set',
            'stone-guardian-armor-cloak' => 'stone-guardian-armor-set',
            'boxy-cyber-matrix-shirt' => 'boxy-cyber-jersey-tee',
            'acid-wash-cargo-denim' => 'camo-tactical-cargo-set',
            'dark-glitch-knitted-sweater' => 'dirty-acid-wash-denim-set',
        ];

        if (isset($redirects[$slug])) {
            return redirect()->route('products.show', $redirects[$slug], 301);
        }

        $product = Product::with(['category', 'images', 'variants'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $recommendations = Product::with(['category', 'primaryImage', 'variants'])
            ->withRealImage()
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'recommendations'));
    }

    public function collection(string $name)
    {
        $collectionName = str_replace('-', ' ', $name);
        
        $products = Product::with(['category', 'primaryImage', 'variants'])
            ->withRealImage()
            ->where('is_active', true)
            ->whereRaw('LOWER(collection_name) = ?', [strtolower($collectionName)])
            ->get();

        $recommendations = Product::with(['category', 'primaryImage', 'variants'])
            ->withRealImage()
            ->where('is_active', true)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('products.collection', compact('collectionName', 'products', 'recommendations'));
    }
}
