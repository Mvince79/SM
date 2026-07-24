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

    public function dropdown(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notificationRecipients()
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'unread_count' => $request->user()
                ->notificationRecipients()
                ->whereNull('read_at')
                ->count(),
            'notifications' => $notifications->map(
                fn ($notification): array => [
                    'id' => $notification->getKey(),
                    'title' => $notification->rendered_title,
                    'message' => $notification->rendered_body,
                    'url' => $notification->action_url
                        ?? route('notifications.show', ['notification' => $notification->getKey()]),
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'time' => $notification->created_at?->diffForHumans(),
                ]
            )->values(),
        ]);
    }

    public function show(Request $request, string $notificationId): View
    {
        $notification = $request->user()
            ->notificationRecipients()
            ->whereKey($notificationId)
            ->firstOrFail();

        return view('notifications.show', ['notification' => $notification]);
    }

    public function read(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()
            ->notificationRecipients()
            ->whereKey($notificationId)
            ->first();

        if ($notification) {
            $notification->update(['read_at' => now()]);
            $unreadCount = $request->user()
                ->notificationRecipients()
                ->whereNull('read_at')
                ->count();
        } else {
            $legacyNotification = $request->user()
                ->notifications()
                ->whereKey($notificationId)
                ->firstOrFail();
            $legacyNotification->markAsRead();
            $unreadCount = $request->user()->unreadNotifications()->count();
        }

        return response()->json([
            'read' => true,
            'unread_count' => $unreadCount,
        ]);
    }
}
