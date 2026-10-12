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

    $query = \App\Models\Product::Where('user_id', $userId)->with('category');

    if(request()->filled('search')){
        $search =request()->string('search')->tostring();
        $query->where(function($q) use ($search){
$q->where('name','like',"%{$search}%")->orWhere('slug','like',"%{$search}%");
        });
    }

    if(request()->filled('stock_status')){
        if(request('stock_status') === 'in_stock'){
            $query->where('stock','>',0);
        }elseif(request('stock_status') === 'out_of_stock'){
            $query->where('stock','=',0);   
        }
    }

    $products = $query->latest()->paginate(12)->withQueryString();
     

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
    public function store(\App\Http\Requests\StoreProductRequest $request)
    {
          $data = $request->validated();
          $data['user_id'] = \Illuminate\Support\Facades\Auth::id();

          if($request->hasFile('image')){$data['image_path']=$request->file('image')->store('products','public');}
            \App\Models\Product::create($data);
            return redirect()->route('owner.products.index')->with('success', 'Product created successfully.');




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
    public function edit(Product $product): View
    {
       abort_unless($product->user_id === \Illuminate\Support\Facades\Auth::id(), 403);

        
        $categories = \App\Models\Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('owner.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\UpdateProductRequest $request, \App\Models\Product $product)
    {
        abort_unless($product->user_id === \Illuminate\Support\Facades\Auth::id(), 403);
        $data = $request->validated();
        if($request->hasFile('image')){
            $data['image_path'] = $request->file('image')->store('products','public');
        }

        unset($data['image']);
        $product->update($data);
        return redirect()->route('owner.products.index')->with('sucessful','productbn updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Product $product)
    {
        abort_unless($product->user_id == \Illuminate\Support\Facades\Auth::id(), 403);
        $product->delete();
        return redirect()->route('owner.products.index')->with('success', 'Product deleted successfully.');
    }
}
