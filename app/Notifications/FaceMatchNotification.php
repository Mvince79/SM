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
            'confidence_level' => $this->match->confidence_level,
            'confidence_label' => $this->match->confidence_label,
            'title' => 'AI gợi ý có hồ sơ khớp',
            'message' => 'AI gợi ý ' . number_format((float) $this->match->score * 100, 2) . '% khớp cho hồ sơ ' . $this->match->missingChild?->full_name . ' (' . $this->match->confidence_label . ')',
            'url' => route('missing.detail', ['id' => $this->match->missing_id]),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
