<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $featuredProducts = Product::with(['category', 'primaryImage', 'variants'])
            ->withRealImage()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->take(8)
            ->get();

        $newArrivals = Product::with(['category', 'primaryImage', 'variants'])
            ->withRealImage()
            ->where('is_active', true)
            ->where('is_new_arrival', true)
            ->take(4)
            ->get();

        return view('home', compact('categories', 'featuredProducts', 'newArrivals'));
    }

    public function about()
    {
        return view('about');
    }

    public function credits()
    {
        $creditedImages = ProductImage::with('product.category')
            ->whereNotNull('credit')
            ->where('credit', '!=', '')
            ->where('is_primary', true)
            ->where('image_path', 'not like', '%placeholder%')
            ->orderBy('product_id')
            ->get();

        return view('credits', compact('creditedImages'));
    }
}
