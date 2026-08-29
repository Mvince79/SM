<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public int $userId;

    public bool $ready = false;

    public string $filter = 'all';

    public function mount(): void
    {
        $this->userId = Auth::id();
    }

    public function getGroupsProperty()
    {
        if (! $this->ready) {
            return [
                'today' => collect(),
                'week' => collect(),
                'older' => collect(),
            ];
        }

        $query = Auth::user()->notificationRecipients()->with('event')->latest();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        }

        // Không giới hạn số lượng cứng (bỏ limit(50)) — trung tâm thông báo hiển thị
        // toàn bộ, phân trang nhóm theo thời gian.
        $all = $query->get();

        return [
            'today' => $all->filter(fn($n) => $n->created_at->isToday()),
            'week' => $all->filter(fn($n) => !$n->created_at->isToday() && $n->created_at->isCurrentWeek()),
            'older' => $all->filter(fn($n) => !$n->created_at->isCurrentWeek()),
        ];
    }

    public function loadNotifications(): void
    {
        $this->ready = true;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function markAsRead(int $notificationId): void
    {
        $notification = Auth::user()->notificationRecipients()->whereKey($notificationId)->first();
        $notification?->update(['read_at' => now()]);

        $this->redirect(route('notifications.show', $notificationId));
    }

    #[On('echo-private:user.{userId},.notification.received')]
    public function refresh(): void
    {
        // Chỉ để re-render khi có thông báo mới qua Reverb, không cần xử lý payload
    }

    public function getIconFor(string $eventType): string
    {
        return match ($eventType) {
            'FACE_MATCH_CONFIRMED' => 'heroicon-o-face-smile',
            'HANDOVER_READY' => 'heroicon-o-check-circle',
            'CASE_APPROVED', 'CASE_APPROVED_IN_PROVINCE' => 'heroicon-o-document-check',
            'CLUE_VERIFIED' => 'heroicon-o-light-bulb',
            default => 'heroicon-o-bell',
        };
    }

    /**
     * CTA theo loại sự kiện — nút hành động ngay trong thẻ (vd "Xác nhận đã nhận
     * lại con" cho HANDOVER_READY / REUNION_FAMILY_REMINDER).
     */
    public function getCtaFor(\App\Models\NotificationRecipient $n): ?array
    {
        $type = $n->event?->event_type;
        $url = $n->action_url ?? route('notifications.show', ['notification' => $n->getKey()]);

        $label = match ($type) {
            'HANDOVER_READY', 'REUNION_FAMILY_REMINDER' => 'Xác nhận đã nhận lại con',
            'REUNION_ADMIN_ESCALATION' => 'Xem và xử lý',
            default => null,
        };

        return $label ? ['label' => $label, 'url' => $url] : null;
    }
};
?>
<div class="space-y-8" wire:init="loadNotifications">
    @if (! $ready)
        <x-skeletons.notification-list
            :rows="5"
            data-notification-center-skeleton
        />
    @else
        <div wire:loading wire:target="refresh">
            <x-skeletons.notification-list :rows="5" />
        </div>

        <div wire:loading.remove wire:target="refresh">
            <div class="flex gap-2">
                <button type="button" wire:click="setFilter('all')"
                    class="rounded-full px-3.5 py-1.5 text-sm font-semibold transition
                        {{ $filter === 'all' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Tất cả
                </button>
                <button type="button" wire:click="setFilter('unread')"
                    class="rounded-full px-3.5 py-1.5 text-sm font-semibold transition
                        {{ $filter === 'unread' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Chưa đọc
                </button>
            </div>

            @if ($this->groups['today']->isEmpty() && $this->groups['week']->isEmpty() && $this->groups['older']->isEmpty())
                <div class="px-4 py-16 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <x-heroicon-o-bell class="h-7 w-7" />
                    </div>
                    <p class="mt-4 text-base font-bold text-slate-700">Chưa có thông báo</p>
                    <p class="mt-1 text-sm text-slate-500">Khi có cập nhật mới, thông báo sẽ hiển thị ở đây.</p>
                </div>
            @else
                @foreach (['today' => 'Hôm nay', 'week' => 'Tuần này', 'older' => 'Cũ hơn'] as $key => $label)
                    @if ($this->groups[$key]->isNotEmpty())
                        <div wire:key="group-{{ $key }}">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-3">{{ $label }}</h3>
                            <div class="space-y-2">
                                @foreach ($this->groups[$key] as $n)
                                    @php($cta = $this->getCtaFor($n))
                                    <div wire:key="notification-{{ $n->id }}" wire:click="markAsRead({{ $n->id }})"
                                        class="flex gap-3 p-4 rounded-2xl border cursor-pointer transition-colors
                                            {{ $n->read_at ? 'bg-white border-slate-200/85' : 'bg-primary/5 border-primary/20' }}">
                                        <div
                                            class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                            <x-dynamic-component :component="$this->getIconFor($n->event?->event_type ?? '')"
                                                class="w-4.5 h-4.5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-bold text-slate-900">{{ $n->rendered_title }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $n->rendered_body }}</p>
                                            <p class="text-[10px] text-slate-400 font-semibold mt-1">
                                                {{ $n->created_at->diffForHumans() }}</p>
                                            @if ($cta)
                                                <a href="{{ $cta['url'] }}" wire:click.stop
                                                    class="mt-2 inline-flex items-center justify-center rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-white transition hover:bg-primary/90">
                                                    {{ $cta['label'] }}
                                                </a>
                                            @endif
                                        </div>
                                        @unless ($n->read_at)
                                            <span class="w-2 h-2 rounded-full bg-primary shrink-0 mt-1.5"></span>
                                        @endunless
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    @endif
</div>
