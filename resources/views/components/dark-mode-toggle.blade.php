<?php

use Illuminate\Support\Facades\Cookie;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * PB-UI-10 — Dark mode toggle.
 *
 * Nguồn sự thật cho theme:
 *   - Đã đăng nhập → cột `users.theme` (đồng bộ đa thiết bị).
 *   - Khách        → cookie "theme" (1 năm), vì chưa có tài khoản để lưu.
 *
 * Giá trị ban đầu ($theme) được layout truyền vào từ SSR (đã đọc DB/cookie
 * trước khi render HTML) để tránh flash trắng — component này chỉ xử lý
 * hành động click sau đó.
 */
new class extends Component
{
    public string $theme = 'light';

    public function mount(?string $theme = null): void
    {
        $this->theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';

        // Nếu user vừa đăng nhập lần đầu và chưa từng lưu lựa chọn trong DB,
        // nhưng trình duyệt đang có cookie theme (đã chọn lúc còn là khách)
        // → đồng bộ 1 lần vào tài khoản.
        $user = auth()->user();
        if ($user && $user->theme === null && Cookie::has('theme')) {
            $cookieTheme = Cookie::get('theme');
            if (in_array($cookieTheme, ['light', 'dark'], true)) {
                $user->forceFill(['theme' => $cookieTheme])->save();
                $this->theme = $cookieTheme;
            }
        }
    }

    public function toggle(): void
    {
        $this->theme = $this->theme === 'dark' ? 'light' : 'dark';

        if ($user = auth()->user()) {
            // Đồng bộ đa thiết bị: lưu theo user, không chỉ localStorage.
            $user->forceFill(['theme' => $this->theme])->save();
        }

        // Cookie đọc được ở server-side (kể cả khách chưa đăng nhập) để lần
        // tải trang tiếp theo SSR đúng theme ngay từ đầu, không bị flash.
        Cookie::queue('theme', $this->theme, 60 * 24 * 365);

        // Đổi ngay lập tức trên DOM hiện tại (không cần reload trang).
        $this->dispatch('theme-changed', theme: $this->theme);
    }

    #[On('theme-changed')]
    public function syncTheme(string $theme): void
    {
        // Giữ các instance toggle khác (nếu có, vd. mobile nav) đồng bộ
        // trong cùng 1 lượt tải trang.
        $this->theme = $theme;
    }
};
?>

<button
    type="button"
    wire:click="toggle"
    wire:loading.attr="disabled"
    x-data
    @theme-changed.window="document.documentElement.setAttribute('data-theme', $event.detail.theme)"
    class="theme-toggle"
    aria-label="{{ $theme === 'dark' ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối' }}"
    title="{{ $theme === 'dark' ? 'Giao diện sáng' : 'Giao diện tối' }}"
>
    {{-- Hiện khi đang ở dark mode → bấm để chuyển sang sáng --}}
    <x-heroicon-o-sun class="icon-sun w-5 h-5" />
    {{-- Hiện khi đang ở light mode → bấm để chuyển sang tối --}}
    <x-heroicon-o-moon class="icon-moon w-5 h-5" />
</button>