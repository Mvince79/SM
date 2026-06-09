<?php

namespace App\Events;

use App\Models\Clue;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewClueSubmitted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Clue $clue)
    {
        $this->clue->loadMissing('missingChild.user');
    }

    public function broadcastAs(): string
    {
        return 'NewClueSubmitted';
    }

    public function broadcastOn(): array
    {
        $user = $this->clue->missingChild?->user;

        return $user ? [new PrivateChannel('user.' . $user->id)] : [];
    }

    public function broadcastWith(): array
    {
        return [
            'clue_id' => $this->clue->id,
            'missing_id' => $this->clue->missing_id,
            'missing_name' => $this->clue->missingChild?->full_name,
            'title' => 'Co manh moi moi',
            'message' => 'Co manh moi moi cho ho so ' . $this->clue->missingChild?->full_name,
            'url' => route('missing.detail', ['id' => $this->clue->missing_id]),
        ];
    }
}
