<?php

namespace App\Services\Notification;

use App\Models\FaceMatchResult;
use App\Models\MissingChild;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Tập trung logic "ai là người nhận" của từng loại thông báo, thay vì rải rác
 * vào observer. Giữ observer mỏng và dễ review: observer chỉ mô tả bảng chuyển
 * trạng thái, còn ai nhận từng event nằm ở đây.
 */
class RecipientResolver
{
    /**
     * Tất cả admin/moderator đang hoạt động — người nhận mọi event "cần duyệt"
     * hoặc "cần can thiệp".
     *
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return User::query()->admins()->active()->get();
    }

    /**
     * Chủ hồ sơ mất tích (gia đình).
     */
    public function familyOf(MissingChild $case): ?User
    {
        return $case->user;
    }

    /**
     * Người đăng tin tìm thấy trẻ khớp với một kết quả đối sánh.
     */
    public function finderOf(FaceMatchResult $match): ?User
    {
        return $match->foundChild?->user;
    }

    /**
     * Người gửi một manh mối.
     *
     * @return Collection<int, User>
     */
    public function clueReporter(?\Illuminate\Database\Eloquent\Model $clue): Collection
    {
        if (! $clue || ! $clue->user_id) {
            return collect();
        }

        $user = User::query()->find($clue->user_id);

        return $user ? collect([$user]) : collect();
    }

    /**
     * Hợp nhất, bỏ trùng, loại bỏ user null.
     *
     * @param  iterable<User|null>  ...$groups
     * @return Collection<int, User>
     */
    public function unique(...$groups): Collection
    {
        return collect($groups)
            ->flatten()
            ->filter(fn ($u): bool => $u instanceof User)
            ->unique(fn (User $u): int => $u->getKey())
            ->values();
    }
}
