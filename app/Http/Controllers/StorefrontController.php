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
}
