<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function center(): View
    {
        return view('notifications.center');
    }

    public function show(Request $request, string $notificationId): View
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        return view('notifications.show', ['notification' => $notification]);
    }

    public function read(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'read' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}