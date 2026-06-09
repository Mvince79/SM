<?php

namespace App\Http\Livewire\Chat;

use Livewire\Component;

class ChatContainer extends Component
{
    public ?int $selectedUserId = null;

    public ?int $selectedMissingId = null;

    protected $listeners = [
        'openChat' => 'openChat',
        'closeChatThread' => 'closeChatThread',
    ];

    public function openChat($userId, $missingId = null)
    {
        $this->selectedUserId = $userId;
        $this->selectedMissingId = $missingId;
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
}
