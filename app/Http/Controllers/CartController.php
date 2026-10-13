<?php

namespace App\Http\Controllers;


use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;


class CartController extends Controller
{
    public function index(Request $request): View{
        $cart = $request->session()->get('cart', []);

        $products = Product::whereIn('id',array_keys($cart))->get()->keyBy('id');

        return view('cart.index', compact('cart','products'));
    }
        public function add(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active && $product->stock > 0, 404);

        $cart = $request->session()->get('cart', []);
        $id = $product->id;
        $quantity = (int) ($cart[$id] ?? 0);

        if ($quantity >= $product->stock) {
            return back()->with('error', 'Maximum available stock reached.');
        }

        $cart[$id] = $quantity + 1;

        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index')
            ->with('success', 'Product added to cart.');
    }

public function update(Request $request, Product $product): RedirectResponse
{
    $validated = $request->validate([
        'quantity' => ['required', 'integer', 'min:1'],
    ]);

    abort_unless($product->is_active, 404);

    $quantity = (int) $validated['quantity'];

    if ($quantity > $product->stock) {
        return back()->with('error', 'Requested quantity exceeds available stock.');
    }

    $cart = $request->session()->get('cart', []);

    if (! array_key_exists($product->id, $cart)) {
        return back()->with('error', 'Product not found in cart.');
    }

    $cart[$product->id] = $quantity;
    $request->session()->put('cart', $cart);

    return redirect()->route('cart.index')
        ->with('success', 'Cart quantity updated.');
}
    public function remove(Request $request, Product $product): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product->id]);

        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index')
            ->with('success', 'Product removed from cart.');
    }
}
