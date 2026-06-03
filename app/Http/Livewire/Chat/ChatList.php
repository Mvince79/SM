<?php

namespace App\Http\Livewire\Chat;

use App\Models\Message;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

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

        $partnerIds = Message::query()
            ->select(
                DB::raw("
                    CASE
                        WHEN from_user_id = {$authId}
                        THEN to_user_id
                        ELSE from_user_id
                    END AS partner_id
                ")
            )
            ->where(function ($query) use ($authId) {
                $query
                    ->where('from_user_id', $authId)
                    ->orWhere('to_user_id', $authId);
            })
            ->groupBy('partner_id')
            ->pluck('partner_id');

        $unreadCounts = Message::query()
            ->select('from_user_id', DB::raw('COUNT(*) as unread_count'))
            ->where('to_user_id', $authId)
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
