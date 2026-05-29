<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Clue;

class ClueSubmittedNotification extends Notification
{
    use Queueable;

    protected Clue $clue;

    public function __construct(Clue $clue)
    {
        $this->clue = $clue;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $missing = $this->clue->missingChild;
        $url = url('/missing/' . ($missing ? $missing->id : ''));

        return (new MailMessage)
            ->subject('Có manh mối mới cho hồ sơ của bạn')
            ->greeting('Xin chào')
            ->line('Có một manh mối mới đã được gửi cho hồ sơ mất tích của bạn.')
            ->line('Mô tả: ' . 
                (strlen($this->clue->description) > 200 ? substr($this->clue->description, 0, 200) . '...' : $this->clue->description))
            ->action('Xem hồ sơ', $url)
            ->line('Cảm ơn bạn đã sử dụng hệ thống.');
    }

    public function toArray($notifiable)
    {
        return [
            'clue_id' => $this->clue->id,
            'missing_id' => $this->clue->missing_id,
            'user_id' => $this->clue->user_id,
            'description' => $this->clue->description,
        ];
    }
}
