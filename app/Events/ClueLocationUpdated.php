<?php

namespace App\Events;

use App\Models\Clue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClueLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Clue $clue,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('missing-child.' . $this->clue->missing_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'clue.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'clue_id' => $this->clue->id,
            'missing_id' => $this->clue->missing_id,
            'lat' => (float) $this->clue->lat,
            'lng' => (float) $this->clue->lng,
            'accuracy' => $this->clue->location_accuracy_m !== null
                ? (float) $this->clue->location_accuracy_m
                : null,
            'gps_shared_at' => $this->clue->gps_shared_at?->toIso8601String(),
            'updated_at' => $this->clue->updated_at?->toIso8601String(),
        ];
    }
}