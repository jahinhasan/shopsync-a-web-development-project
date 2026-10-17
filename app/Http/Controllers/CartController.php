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
        $removed = $this->purgeUnavailableCartProducts($request);

        $cart = $request->session()->get('cart', []);

        $products = Product::query()
            ->availableToCustomers()
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        if ($removed) {
            $request->session()->flash(
                'error',
                'Unavailable products were removed from your cart.'
            );
        }

        return view('cart.index', compact('cart', 'products'));
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->isAvailableToCustomers(), 404);

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

        abort_unless($product->isAvailableToCustomers(), 404);

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


    public function checkout(): View|RedirectResponse
    {
        $cart = session('cart', []);
        $removed = $this->purgeUnavailableCartProducts(request());


        if (empty($cart)) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty. Please add products before checkout.');
        }

        $products = Product::whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        return view('cart.checkout', compact('cart', 'products'));
    }


    public function placeOrder(Request $request, Product $products): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'payment_method' => ['required', 'in:cod,bkash,nagad'],
            'payment_reference' => [
                'nullable',
                'required_if:payment_method,bkash,nagad',
                'string',
                'max:120',
            ],
            'voucher_code' => ['nullable', 'string', 'max:40']
        ]);

        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'your cart is empty');
        }

        foreach ($cart as $quantity) {
            if (! is_numeric($quantity) || (int) $quantity != $quantity || $quantity < 1) {
                return redirect()->route('cart.index')->with('error', 'Invalid quantity in cart');
            }
        }


        $voucherCode = $validated['voucher_code'] ?? null;

        // Validate the voucher before anything is written so an invalid code
        // cannot leave a placed order behind.
        if (filled($voucherCode)) {
            $cartProducts = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

            app(\App\Services\CustomerRewardService::class)->previewVoucherForCart(
                $request->user(),
                $voucherCode,
                $cart,
                $cartProducts
            );
        }

        $order = DB::transaction(function () use ($cart, $validated, $voucherCode) {
            $products = Product::whereIn('id', array_keys($cart))->lockForUpdate()->get()->keyBy('id');


            $total = 0;
            foreach ($cart as $id => $quantity) {
                $product = $products->get($id);
                $quantity = (int) $quantity;
                if (
                    ! $product || !$product->isAvailableToCustomers() ||  $quantity > $product->stock
                ) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cart' => 'A product is unavailable or its stock has changed. please review your cart.',
                    ]);
                }

                $total += (float) $product->price * $quantity;
            }
            $paymentMethod = $validated['payment_method'];

            if (
                in_array($paymentMethod, ['bkash', 'nagad'], true)
                && blank(config("shopsync.payment_numbers.{$paymentMethod}"))
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'payment_method' => 'This payment method is not configured yet.',
                ]);
            }


            $order = Order::Create([
                'user_id' => Auth::id(),
                'customer_name' => $validated['customer_name'],
                'phone' => $validated['phone'],
                'shipping_address' => $validated['shipping_address'],
                'total' => $total,
                'status' => 'pending',
                'order_number' => 'ORD-' . Str::upper(str::random(12)),


                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'payment_reference' => in_array($paymentMethod, ['bkash', 'nagad'], true)
                    ? trim($validated['payment_reference'])
                    : null,
                'payment_submitted_at' => in_array($paymentMethod, ['bkash', 'nagad'], true)
                    ? now()
                    : null,
                'voucher_discount_total' => 0,
                'voucher_code' => null,

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
            if (filled($voucherCode)) {
                app(\App\Services\CustomerRewardService::class)->applyVoucherToOrder(
                    Auth::user(),
                    $order,
                    $voucherCode
                );
            }

            return $order;
        });

        $request->session()->forget('cart');
        return redirect()->route('orders.show', $order)->with('success', 'Order placed successfully. Your order number is: ' . $order->order_number);
    }

    private function purgeUnavailableCartProducts(Request $request): bool
    {
        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return false;
        }

        $products = Product::withTrashed()
            ->whereIn('id', array_keys($cart))
            ->with('owner.shopApplication')
            ->get()
            ->keyBy('id');

        $removed = false;

        foreach (array_keys($cart) as $productId) {
            $product = $products->get($productId);

            if (
                !$product
                || $product->trashed()
                || !$product->isAvailableToCustomers()
            ) {
                unset($cart[$productId]);
                $removed = true;
            }
        }

        $request->session()->put('cart', $cart);

        return $removed;
    }
}
