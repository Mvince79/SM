@php
    // Banner định vị "kênh hỗ trợ" — toàn trang, dismissible (localStorage).
    // Giữ vị thế: hệ thống chỉ lan tỏa thông tin, không thay thế cơ quan chức năng.
@endphp
<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<div x-data="{ dismissed: localStorage.getItem('hide_emergency_disclaimer') === '1' }"
     x-show="!dismissed"
     x-cloak
     class="bg-slate-900 text-slate-100 text-xs sm:text-sm border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 py-2 flex items-center gap-3">
        <x-heroicon-o-information-circle class="w-5 h-5 text-amber-400 shrink-0" />
        <p class="flex-1 leading-relaxed font-medium">
            Nếu có em bé mất tích hoặc gặp nguy hiểm, gọi ngay
            <a href="tel:111" class="font-black text-amber-300 underline decoration-amber-300/50">111</a> /
            <a href="tel:113" class="font-black text-amber-300 underline decoration-amber-300/50">113</a>.
            TìmTrẻ.vn chỉ hỗ trợ <span class="font-bold text-white">lan tỏa thông tin</span> và kết nối — không thay thế cơ quan chức năng.
        </p>
        <button type="button"
            @click="localStorage.setItem('hide_emergency_disclaimer', '1'); dismissed = true"
            class="shrink-0 text-slate-400 hover:text-white p-1 -mr-1 transition-colors"
            aria-label="Đóng thông báo">
            <x-heroicon-o-x-mark class="w-4 h-4" />
        </button>
    </div>
</div>
