<?php

namespace App\Notifications;

use App\Models\FaceMatchResult;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FaceMatchNotification extends Notification
{
    use Queueable;

    public function __construct(public FaceMatchResult $match)
    {
        $this->match->loadMissing('missingChild');
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'face_match_found',
            'match_id' => $this->match->id,
            'missing_id' => $this->match->missing_id,
            'found_id' => $this->match->found_id,
            'score' => (float) $this->match->score,
            'title' => 'AI goi y co ho so khop',
            'message' => 'AI goi y ' . number_format((float) $this->match->score * 100, 2) . '% khop cho ho so ' . $this->match->missingChild?->full_name,
            'url' => route('missing.detail', ['id' => $this->match->missing_id]),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
