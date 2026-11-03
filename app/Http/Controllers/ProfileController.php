<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ShopApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // Accounts referenced by business records (orders, products, shop
        // applications, reviews, chat) are anonymized instead of deleted so
        // the records other parties rely on survive; brand-new accounts are
        // removed outright.
        $hasBusinessRecords =
            Order::query()->where('user_id', $user->id)->exists()
            || Product::query()->where('user_id', $user->id)->exists()
            || ShopApplication::query()->where('user_id', $user->id)->exists()
            || ProductReview::query()->where('user_id', $user->id)->exists()
            || ChatMessage::query()->where('sender_id', $user->id)->exists();

        if ($hasBusinessRecords) {
            // A randomized password disables login; the placeholder email
            // frees the original address for a future account.
            $user->forceFill([
                'name' => 'Former user #' . $user->id,
                'email' => 'former-user-' . $user->id . '@shopsync.invalid',
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => null,
                'remember_token' => null,
            ])->save();
        } else {
            $user->delete();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
