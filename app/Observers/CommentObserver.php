<?php

namespace App\Observers;

use App\Models\Comment;
use Illuminate\Support\Facades\Log;

class CommentObserver
{
    /**
     * Các mẫu câu thường gặp trong lừa đảo "tìm trẻ giả" — khớp 1 trong các mẫu
     * này sẽ buộc bình luận vào hàng chờ duyệt thay vì hiện công khai ngay.
     */
    protected array $suspiciousPatterns = [
        '/chuyển khoản.*phí/iu',
        '/đặt cọc.*(nhận|giao).*(bé|con|trẻ)/iu',
        '/liên hệ.*zalo.*riêng/iu',
        '/inbox.*riêng.*(để|nhận)/iu',
    ];

    /**
     * Tuổi tối thiểu của tài khoản (ngày) để được auto-approve.
     * Tài khoản mới hơn ngưỡng này luôn vào hàng chờ duyệt.
     */
    protected int $minAccountAgeDays = 7;

    /**
     * Handle the Comment "creating" event.
     * Chạy TRƯỚC khi INSERT — quyết định is_approved ngay từ đầu,
     * tránh để bình luận lừa đảo lộ ra công khai dù chỉ 1 giây.
     */
    public function creating(Comment $comment): void
    {
        try {
            if ($this->isFromNewAccount($comment) || $this->isSuspiciousContent($comment->body)) {
                $comment->is_approved = 0;
            }
        } catch (\Throwable $e) {
            // An toàn theo hướng thận trọng: nếu check lỗi, vẫn chặn duyệt tự động
            // thay vì để lọt — khác với các Observer khác chỉ log rồi cho qua.
            $comment->is_approved = 0;
            Log::warning('CommentObserver safety check failed, forcing manual review: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment): void
    {
        try {
            activity()
                ->performedOn($comment)
                ->event('created')
                ->withProperties([
                    'is_approved' => $comment->is_approved,
                    'commentable_type' => $comment->commentable_type,
                    'commentable_id' => $comment->commentable_id,
                ])
                ->log($comment->is_approved
                    ? 'Bình luận mới đã hiện công khai'
                    : 'Bình luận mới đang chờ duyệt (nghi vấn hoặc tài khoản mới)');
        } catch (\Throwable $e) {
            Log::warning('Activity log failed: ' . $e->getMessage());
        }

        Log::info("CommentObserver (created): comment #{$comment->id} is_approved={$comment->is_approved}");
    }

    /**
     * Tài khoản tạo dưới {$minAccountAgeDays} ngày → coi là mới, cần duyệt thủ công.
     */
    private function isFromNewAccount(Comment $comment): bool
    {
        $user = $comment->user;

        if (!$user || !$user->created_at) {
            return false;
        }

        return $user->created_at->gt(now()->subDays($this->minAccountAgeDays));
    }

    /**
     * So khớp nội dung bình luận với các mẫu câu lừa đảo thường gặp.
     */
    private function isSuspiciousContent(?string $body): bool
    {
        if (!$body) {
            return false;
        }

        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $body)) {
                return true;
            }
        }

        return false;
    }
}