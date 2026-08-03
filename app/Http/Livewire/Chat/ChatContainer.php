<?php

namespace App\Http\Livewire\Chat;

use App\Models\AdminChatAssignment;
use Livewire\Component;

class ChatContainer extends Component
{
    public ?int $selectedUserId = null;

    public ?int $selectedMissingId = null;

    protected $listeners = [
        'openChat' => 'openChat',
        'closeChatThread' => 'closeChatThread',
    ];

    public function openChat($userId, $missingId = null): void
    {
        $userId = (int) $userId;

        if (! $this->hasActiveAssignmentWith($userId)) {
            $this->closeChatThread();

            return;
        }

        $this->selectedUserId = $userId;
        $this->selectedMissingId = $missingId !== null ? (int) $missingId : null;
    }

    public function closeChatThread(): void
    {
        $this->selectedUserId = null;
        $this->selectedMissingId = null;
    }

    public function render()
    {
        return view('livewire.chat.chat-container');
    }

    private function hasActiveAssignmentWith(int $userId): bool
    {
        $currentUser = auth()->user();

        if (! $currentUser) {
            return false;
        }

        return AdminChatAssignment::query()
            ->where('status', AdminChatAssignment::STATUS_ACTIVE)
            ->when(
                $currentUser->isAdmin(),
                fn ($query) => $query
                    ->where('admin_user_id', $currentUser->getKey())
                    ->where('requester_user_id', $userId),
                fn ($query) => $query
                    ->where('requester_user_id', $currentUser->getKey())
                    ->where('admin_user_id', $userId),
            )
            ->exists();
    }
}
