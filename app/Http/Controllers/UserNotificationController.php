<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;

class UserNotificationController extends Controller
{
    public function index(): View
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(string $notificationId): RedirectResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($notificationId);

        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(): RedirectResponse
    {
        Auth::user()
            ->unreadNotifications
            ->each(fn ($notification) => $notification->markAsRead());

        return back()->with('success', 'All notifications marked as read.');
    }
}
