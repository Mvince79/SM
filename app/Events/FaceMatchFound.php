<?php

namespace App\Events;

use App\Models\FaceMatchResult;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * H-01/H-02 — Sự kiện "AI gợi ý khớp".
 *
 * CHỈ đóng vai trò realtime broadcast (cho socket). Việc ghi thông báo vào
 * DB/chuông/email do TriggerFaceMatchJob gọi Notifier (TYPE_FACE_MATCH_SUGGESTED)
 * lo — không phát ở đây để tránh nhân đôi và để có nguồn phát sinh rõ ràng
 * ("AI đối sánh tự động").
 */
class FaceMatchFound implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public FaceMatchResult $match)
    {
        $this->match->loadMissing(['missingChild.user', 'foundChild.user']);
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
            'confidence_level' => $this->match->confidence_level,
            'confidence_label' => $this->match->confidence_label,
            'missing_name' => $this->match->missingChild?->full_name,
            'title' => 'AI gợi ý có hồ sơ khớp',
            'message' => 'AI gợi ý ' . number_format((float) $this->match->score * 100, 2) . '% khớp cho hồ sơ ' . $this->match->missingChild?->full_name . ' (' . $this->match->confidence_label . ')',
            'url' => route('missing.detail', ['id' => $this->match->missing_id]),
        ];
    }

    private function usersToNotify()
    {
        $caseUsers = collect([
            $this->match->missingChild?->user,
            $this->match->foundChild?->user,
        ])->filter();

        $admins = User::query()->admins()->get();

        return $caseUsers
            ->merge($admins)
            ->unique('id')
            ->values();
    }
}
