<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public int $userId;

    public function mount(): void
    {
        $this->userId = Auth::id();
    }

    public function getGroupsProperty()
    {
        $all = Auth::user()->notifications()->latest()->limit(50)->get();

        return [
            'today' => $all->filter(fn($n) => $n->created_at->isToday()),
            'week' => $all->filter(fn($n) => !$n->created_at->isToday() && $n->created_at->isCurrentWeek()),
            'older' => $all->filter(fn($n) => !$n->created_at->isCurrentWeek()),
        ];
    }

    public function markAsRead(string $notificationId): void
    {
        Auth::user()->notifications()->whereKey($notificationId)->first()?->markAsRead();

        $this->redirect(route('notifications.show', $notificationId));
    }

    #[On('echo-private:user.{userId},.notification.received')]
    public function refresh(): void
    {
        // Chỉ để re-render khi có thông báo mới qua Reverb, không cần xử lý payload
    }

    public function getIconFor(string $type): string
    {
        return match ($type) {
            'ai_processing_failed' => 'heroicon-o-exclamation-triangle',
            'handover_ready' => 'heroicon-o-check-circle',
            'face_match' => 'heroicon-o-face-smile',
            'clue_submitted' => 'heroicon-o-light-bulb',
            default => 'heroicon-o-bell',
        };
    }
};
?>
<div class="space-y-8">
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
                            <div wire:key="notification-{{ $n->id }}" wire:click="markAsRead('{{ $n->id }}')"
                                class="flex gap-3 p-4 rounded-2xl border cursor-pointer transition-colors
                                    {{ $n->read_at ? 'bg-white border-slate-200/85' : 'bg-primary/5 border-primary/20' }}">
                                <div
                                    class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                    <x-dynamic-component :component="$this->getIconFor($n->data['type'] ?? '')"
                                        class="w-4.5 h-4.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-slate-900">{{ $n->data['title'] ?? 'Thông báo' }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $n->data['message'] ?? '' }}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-1">
                                        {{ $n->created_at->diffForHumans() }}</p>
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