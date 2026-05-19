@props(['case'])

<div class="case-card">
    <div class="case-card-img">
        <img src="{{ $case->photo_url ?? 'https://api.dicebear.com/7.x/thumbs/svg?seed=' . $case->id }}" alt="" />
        <span
            class="absolute top-3 left-3 bg-primary text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl flex items-center gap-1">
            <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span> Đang tìm gấp
        </span>
    </div>
    <div class="case-card-body">
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <h3 class="case-card-name">{{ $case->child_name }}</h3>
                <span class="text-xs font-extrabold text-slate-400">{{ $case->age }} tuổi</span>
            </div>
            <div class="case-card-location"><span>📍</span> {{ $case->last_seen_location }}</div>
            <p class="case-card-desc">{{ $case->description }}</p>
        </div>
        <div class="case-card-footer">
            <span class="badge badge-urgent">Đang tìm gấp</span>
            <a href="#" class="text-xs font-extrabold text-primary hover:underline flex items-center gap-1">
                Xem hồ sơ <span class="text-[10px]">→</span>
            </a>
        </div>
    </div>
</div>