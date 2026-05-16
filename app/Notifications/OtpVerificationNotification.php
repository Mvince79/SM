<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $otp
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Mã xác thực OTP — Tìm Kiếm Trẻ Em Thất Lạc')
            ->greeting('Xin chào ' . $notifiable->full_name . '!')
            ->line('Cảm ơn bạn đã đăng ký tài khoản trên hệ thống **Tìm Kiếm Trẻ Em Thất Lạc**.')
            ->line('Mã xác thực OTP của bạn là:')
            ->line('## ' . $this->otp)
            ->line('Mã có hiệu lực trong **15 phút** kể từ thời điểm nhận email này.')
            ->line('Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.')
            ->salutation('Trân trọng, đội ngũ hỗ trợ.');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}