<?php

namespace App\Notifications;

use App\Models\FoundChild;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FoundChildApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FoundChild $found)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'found_child_approved',
            'found_id' => $this->found->id,
            'title' => 'Tin báo trẻ tìm được đã được duyệt',
            'message' => 'Tin báo trẻ bạn tìm được (phát hiện ' . ($this->found->found_date?->format('d/m/Y') ?? '') . ') đã được duyệt và công khai.',
            'url' => route('found-children.show', $this->found),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
