<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentHidden implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Comment $comment
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('missing-child.' . $this->comment->commentable_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.hidden';
    }

    public function broadcastWith(): array
    {
        return [
            'comment_id' => $this->comment->id,
            'is_hidden' => $this->comment->is_hidden,
        ];
    }
}