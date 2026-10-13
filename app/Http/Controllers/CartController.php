<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Models\OrderIdem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\InventoryTransaction;
class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $request->session()->get('cart', []);

        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

        return view('cart.index', compact('cart', 'products'));
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

    public function checkout(): View
    {
        $cart = session('cart', []);
        abort_if(empty($cart), redirect()->route('cart.index')->getStatusCode());

        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

        return view('cart.checkout', compact('cart', 'products'));
    }

    public function placeOrder(Request $request, Product $products): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:2000']
        ]);

        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'your cart is empty');
        }

        foreach($cart as $quantity){
            if(! is_numeric($quantity) || (int) $quantity != $quantity || $quantity < 1){
                return redirect()->route('cart.index')->with('error', 'Invalid quantity in cart');
            }
        }

        $order = DB::transaction(function () use ($cart, $validated) {
            $products = Product::whereIn('id', array_keys($cart))->lockForUpdate()->get()->keyBy('id');


            $total = 0;
            foreach ($cart as $id => $quantity) {
                $product = $products->get($id);
                $quantity = (int) $quantity;
                if (
                    ! $product || ! $product->is_active ||  $quantity > $product->stock
                ) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cart' => 'A product is unavailable or its stock has changed. please review your cart.',
                    ]);
                }

                $total += (float) $product->price * $quantity;
            }

            $order = Order::Create([
                'user_id' => Auth::id(),
                'customer_name' => $validated['customer_name'],
                'phone' => $validated['phone'],
                'shipping_address' => $validated['shipping_address'],
                'total' => $total,
                'status' => 'pending',
                'order_number' => 'ORD-' . Str::upper(str::random(12)),
                'payment_status' => 'unpaid',
                'payment_method' => 'cod',

            ]);
            foreach ($cart as $id => $quantity) {
                $product = $products->get($id);
                OrderIdem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,

                    'quantity' => $quantity,
                    'subtotal' => (float)$product->price * $quantity,
                ]);
              $quantityBefore = (int) $product->stock;
$quantity = (int) $quantity;

$product->decrement('stock', $quantity);

$quantityAfter = $quantityBefore - $quantity;

InventoryTransaction::create([
    'product_id' => $product->id,
    'user_id' => $product->user_id,
    'order_id' => $order->id,
    'type' => 'sale',
    'quantity_before' => $quantityBefore,
    'quantity_change' => -$quantity,
    'quantity_after' => $quantityAfter,
    'reason' => 'Stock reduced for order ' . $order->order_number,
]);
            }
            return $order;
        });

        $request->session()->forget('cart');
        return redirect()->route('cart.index')->with('success', 'Order placed successfully. Your order number is: ' . $order->order_number);
    }
}
