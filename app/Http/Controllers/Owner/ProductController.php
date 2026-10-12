<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Product;



class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index(): View
{
    $userId = \Illuminate\Support\Facades\Auth::id();

    $products = Product::where('user_id', $userId)
        ->with('category')
        ->latest()
        ->paginate(12);

    return view('owner.products.index', compact('products'));
}
    /**
     * Show the form for creating a new resource.
     */
public function create(): View
{
    $categories = \App\Models\Category::where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('owner.products.create', compact('categories'));
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
