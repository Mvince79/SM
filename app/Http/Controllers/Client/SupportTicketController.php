<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\NotificationEvent;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Services\Notification\Notifier;
use App\Services\Notification\RecipientResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SupportTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id', Auth::id())
            ->with(['replies' => fn($query) => $query->publicOnly()->with('user')->oldest()])
            ->latest()
            ->get();

        return view('support.index', compact('tickets'));
    }

    public function create()
    {
        return view('support.create');
    }

    public function store(StoreSupportTicketRequest $request)
    {
        $user = Auth::user();

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'category' => $request->validated('category'),
            'subject' => $request->validated('subject'),
            'description' => $request->validated('description'),
            'priority' => $request->validated('priority') ?: 'MEDIUM',
        ]);

        // Báo admin/moderator đang active (P0): ticket mới phải hiện lên chuông
        // + email ngay, thay vì chờ admin tự mở danh sách. Không để lỗi thông
        // báo làm chết luồng tạo ticket của người dùng.
        try {
            $notifier = app(Notifier::class);
            $recipients = app(RecipientResolver::class)->admins();

            $notifier->send(
                NotificationEvent::TYPE_TICKET_CREATED,
                $ticket,
                $recipients,
                [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'full_name' => $user->full_name,
                    'source' => NotificationEvent::SOURCE_COMMUNITY,
                ],
                $user->id,
            );
        } catch (\Throwable $e) {
            Log::error('Không gửi được thông báo ticket mới cho admin', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('support.index')
            ->with('ticket_submitted', 'Đã gửi yêu cầu hỗ trợ. Chúng tôi sẽ phản hồi sớm nhất có thể.');
    }
}