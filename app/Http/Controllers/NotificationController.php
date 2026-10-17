<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications', [
            'notifications' => $request->user()->notifications()->simplePaginate(20),
        ]);
    }

    /** Mark one notification read and follow its link. */
    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        Cache::forget('unread.'.$request->user()->id);

        $url = $notification->data['url'] ?? null;

        // Only follow internal paths we generated ourselves.
        $safe = is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//');

        return redirect($safe ? $url : route('notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
            Cache::forget('unread.'.$request->user()->id);
        return back()->with('status', 'All notifications marked as read.');
    }
}
