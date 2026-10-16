<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopApplication;
use Illuminate\Support\Facades\Auth;

class ShopApplicationController extends Controller
{
        public function create()
    {
        $application = Auth::user()->shopApplication;

        if ($application) {
            return redirect()->route('shop-application.status');
        }

        return view('shop-application.create');
    }


        public function status()
    {
        $application = Auth::user()
            ->shopApplication()
            ->with('media')
            ->first();

        abort_unless($application, 404);

        return view('shop-application.status', compact('application'));
    }
}
