<?php

namespace App\Http\Livewire\Chat;

use App\Models\AdminChatAssignment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatList extends Component
{
    #[On('message-received')]
    #[On('messages-read')]
    #[On('message-sent')]
    public function refreshList(): void
    {
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $authId = auth()->id();

        $currentUser = auth()->user();
        $partnerIds = AdminChatAssignment::query()
            ->where('status', AdminChatAssignment::STATUS_ACTIVE)
            ->when(
                $currentUser->isAdmin(),
                fn ($query) => $query->where('admin_user_id', $authId)->pluck('requester_user_id'),
                fn ($query) => $query->where('requester_user_id', $authId)->pluck('admin_user_id'),
            );

        $unreadCounts = Message::query()
            ->select('from_user_id', DB::raw('COUNT(*) as unread_count'))
            ->where('to_user_id', $authId)
            ->whereIn('from_user_id', $partnerIds)
            ->whereNull('read_at')
            ->groupBy('from_user_id')
            ->pluck('unread_count', 'from_user_id');

        $users = User::query()
            ->whereIn('id', $partnerIds)
            ->get()
            ->map(function (User $user) use ($unreadCounts) {
                $user->unread_count = (int) ($unreadCounts[$user->id] ?? 0);

                return $user;
            });

        return view('livewire.chat.chat-list', [
            'users' => $users,
        ]);
    }
}
