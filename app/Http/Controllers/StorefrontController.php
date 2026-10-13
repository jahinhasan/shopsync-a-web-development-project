<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
       $products = Product::query()->where('is_active', true)->where('stock','>',0)->with('category')->latest()->paginate(12);
   
       return view('shop.index', compact('products'));
    }


    public function show(Product $product): View
    {
        abort_unless($product->is_active && $product->stock > 0, 404);
        $product->load('category');
        return view('shop.show', compact('product'));
    }
}
