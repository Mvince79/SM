<?php

namespace App\Http\Livewire\Chat;

use App\Events\AdminChatMessageSent;
use App\Events\MessageSent;
use App\Models\AdminChatAssignment;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageSanitizerService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatBox extends Component
{
    use WithFileUploads;

    public int $receiverId;

    public ?int $missingId = null;

    public string $body = '';

    public $photo;

    public int $refreshKey = 0;

    public int $messageCount = 30;

    public bool $hasMoreMessages = false;

    public function mount(?int $missing, int $user): void
    {
        $this->missingId = $missing;
        $this->receiverId = $user;
    }

    #[On('message-received')]
    public function messageReceived()
    {
        $this->refreshKey++;
        $this->markAsRead();
        $this->dispatch('$refresh');
    }

    public function loadMore(): void
    {
        $this->messageCount += 30;
    }

    private function messagesQuery()
    {
        return Message::query()
            ->where(function ($query) {
                $query
                    ->where([
                        'from_user_id' => auth()->id(),
                        'to_user_id' => $this->receiverId,
                    ])
                    ->orWhere([
                        'from_user_id' => $this->receiverId,
                        'to_user_id' => auth()->id(),
                    ]);
            });
    }

    public function loadMessages(): Collection
    {
        $this->hasMoreMessages = (clone $this->messagesQuery())->count() > $this->messageCount;

        return $this->messagesQuery()
            ->with('missingChild')
            ->orderBy('created_at', 'desc')
            ->take($this->messageCount)
            ->get()
            ->reverse()
            ->values();
    }

    public function send(): void
    {
        $this->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'], // max 5MB
        ]);

        if (empty(trim($this->body)) && ! $this->photo) {
            $this->addError('body', 'Vui lòng nhập tin nhắn hoặc chọn ảnh.');

            return;
        }

        if (! $this->isAssignedConversationAllowed()) {
            $this->addError('body', 'Cuộc chat này đang được một quản trị viên khác phụ trách.');

            return;
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($this->photo) {
            $attachmentPath = $this->photo->store('chat-attachments', 'private');
            $attachmentType = 'IMAGE';
        }

        $message = Message::create([
            'from_user_id' => auth()->id(),
            'to_user_id' => $this->receiverId,
            'missing_id' => $this->missingId,
            'body' => trim($this->body) ?: null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        $assignment = AdminChatAssignment::query()
            ->where('requester_user_id', $message->from_user_id)
            ->where('admin_user_id', $message->to_user_id)
            ->where('status', AdminChatAssignment::STATUS_ACTIVE)
            ->first();

        if ($assignment) {
            broadcast(new AdminChatMessageSent($assignment, $message))->toOthers();
        }

        $this->reset(['body', 'photo']);
        $this->dispatch('message-sent');
        $this->dispatch('scroll-to-bottom');
    }

    private function isAssignedConversationAllowed(): bool
    {
        $currentUser = auth()->user();
        $receiver = User::query()->find($this->receiverId);

        if (! $currentUser || ! $receiver) {
            return false;
        }

        if ($currentUser->isAdmin() === $receiver->isAdmin()) {
            return false;
        }

        $admin = $currentUser->isAdmin() ? $currentUser : $receiver;
        $requester = $currentUser->isAdmin() ? $receiver : $currentUser;

        return AdminChatAssignment::query()
            ->where('requester_user_id', $requester->getKey())
            ->where('admin_user_id', $admin->getKey())
            ->where('status', AdminChatAssignment::STATUS_ACTIVE)
            ->exists();
    }

    public function markAsRead(): void
    {
        $updated = Message::query()
            ->where('from_user_id', $this->receiverId)
            ->where('to_user_id', auth()->id())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        if ($updated > 0) {
            $this->dispatch('messages-read'); // Tells ChatList to refresh unread badge
        }
    }

    public function render()
    {
        $this->markAsRead();
        $sanitizer = app(MessageSanitizerService::class);
        $messages = $this->loadMessages()->each(function (Message $message) use ($sanitizer): void {
            $message->setAttribute('display_body', $sanitizer->sanitizeForMessage($message));
        });

        $receiver = User::find($this->receiverId);

        return view('livewire.chat.chat-box', [
            'messages' => $messages,
            'receiver' => $receiver,
            'hasMore' => $this->hasMoreMessages,
        ])->layout('layouts.app');
    }
}
