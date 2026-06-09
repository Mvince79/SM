<?php

namespace App\Events;

use App\Models\MissingChild;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostStatusChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public MissingChild $missingChild)
    {
        $this->missingChild->loadMissing('user');
    }

    public function broadcastAs(): string
    {
        return 'PostStatusChanged';
    }

    public function broadcastOn(): array
    {
        return $this->missingChild->user
            ? [new PrivateChannel('user.' . $this->missingChild->user->id)]
            : [];
    }

    public function broadcastWith(): array
    {
        return [
            'missing_id' => $this->missingChild->id,
            'status' => $this->missingChild->status,
            'missing_name' => $this->missingChild->full_name,
            'title' => 'Trang thai bai dang da thay doi',
            'message' => 'Ho so ' . $this->missingChild->full_name . ' hien co trang thai ' . $this->missingChild->status,
            'url' => route('missing.detail', ['id' => $this->missingChild->id]),
        ];
    }
}
