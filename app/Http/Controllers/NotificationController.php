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

    public function show(Request $request, int $notificationId): View
    {
        $notification = $request->user()
            ->notificationRecipients()
            ->whereKey($notificationId)
            ->firstOrFail();

        return view('notifications.show', ['notification' => $notification]);
    }

    public function read(Request $request, int $notificationId): JsonResponse
    {
        $notification = $request->user()
            ->notificationRecipients()
            ->whereKey($notificationId)
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        return response()->json([
            'read' => true,
            'unread_count' => $request->user()->notificationRecipients()->whereNull('read_at')->count(),
        ]);
    }
}