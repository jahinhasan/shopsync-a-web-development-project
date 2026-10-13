<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class OderController extends Controller
{
    public function index(): View {
        $orders = Order::where('user_id',Auth::id())->latest()->paginate(12);

        return view('order.index',compact('orders'));
    }

    public function show(Order $order): View {
        abort_unless($order->user_id===Auth::id(),403);
        $order->load('items');
        return view('order.show',compact('order'));
    }




}
