<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Support\Facades\Auth;

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
        SupportTicket::create([
            'user_id' => Auth::id(),
            'category' => $request->validated('category'),
            'subject' => $request->validated('subject'),
            'description' => $request->validated('description'),
            'priority' => $request->validated('priority') ?: 'MEDIUM',
        ]);

        return redirect()
            ->route('support.index')
            ->with('ticket_submitted', 'Đã gửi yêu cầu hỗ trợ. Chúng tôi sẽ phản hồi sớm nhất có thể.');
    }
}