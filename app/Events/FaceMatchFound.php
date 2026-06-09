<?php

namespace App\Events;

use App\Models\FaceMatchResult;
use App\Notifications\FaceMatchNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FaceMatchFound implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public FaceMatchResult $match)
    {
        $this->match->loadMissing(['missingChild.user', 'foundChild.user']);

        foreach ($this->usersToNotify() as $user) {
            $user->notify(new FaceMatchNotification($this->match));
        }
    }

    public function broadcastAs(): string
    {
        return 'FaceMatchFound';
    }

    public function broadcastOn(): array
    {
        return $this->usersToNotify()
            ->map(fn ($user) => new PrivateChannel('user.' . $user->id))
            ->all();
    }

    public function broadcastWith(): array
    {
        return [
            'match_id' => $this->match->id,
            'missing_id' => $this->match->missing_id,
            'found_id' => $this->match->found_id,
            'score' => (float) $this->match->score,
            'missing_name' => $this->match->missingChild?->full_name,
            'title' => 'AI gợi ý có hồ sơ khớp',
            'message' => 'AI gợi ý ' . number_format((float) $this->match->score * 100, 2) . '% khớp cho hồ sơ ' . $this->match->missingChild?->full_name,
            'url' => route('missing.detail', ['id' => $this->match->missing_id]),
        ];
    }

    private function usersToNotify()
    {
        return collect([
            $this->match->missingChild?->user,
            $this->match->foundChild?->user,
        ])->filter()->unique('id')->values();
    }
}
