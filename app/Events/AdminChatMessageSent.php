<?php

namespace App\Events;

use App\Models\AdminChatAssignment;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AdminChatAssignment $assignment,
        public Message $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-chat-assignment.'.$this->assignment->getKey()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'admin.chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'assignment_id' => $this->assignment->getKey(),
            'message_id' => $this->message->getKey(),
            'requester_user_id' => $this->assignment->requester_user_id,
            'admin_user_id' => $this->assignment->admin_user_id,
            'missing_id' => $this->message->missing_id,
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
