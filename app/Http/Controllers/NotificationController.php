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
            ->with('event')
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
                    'event_type' => $notification->event?->event_type,
                    'source_label' => $notification->event?->sourceLabel(),
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
        // Không còn nhánh fallback hệ cũ (bảng `notifications` qua Notifiable): mọi
        // thông báo đều nằm ở notification_recipients. firstOrFail() đảm bảo thông
        // báo của người khác / không tồn tại trả 404 (Model Not Found), đúng phân
        // quyền; thông báo của chính user (kể cả đã đọc) vẫn được cập nhật bình thường.
        $notification = $request->user()
            ->notificationRecipients()
            ->whereKey($notificationId)
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        return response()->json([
            'read' => true,
            'unread_count' => $request->user()
                ->notificationRecipients()
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()
            ->notificationRecipients()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'read' => true,
            'unread_count' => 0,
        ]);
    }
}
