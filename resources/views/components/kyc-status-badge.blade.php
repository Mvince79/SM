@props(['status' => 'NONE', 'reason' => null, 'banner' => false])

@php
    $config = match ($status) {
        'VERIFIED' => [
            'label' => 'Đã xác thực',
            'bg' => 'bg-success-light',
            'text' => 'text-success',
            'border' => 'border-success/35',
            'icon' => 'heroicon-o-check-circle',
        ],
        'PENDING' => [
            'label' => 'Đang chờ duyệt',
            'bg' => 'bg-amber-50',
            'text' => 'text-amber-600',
            'border' => 'border-amber-300',
            'icon' => 'heroicon-o-clock',
        ],
        'REJECTED' => [
            'label' => 'Bị từ chối',
            'bg' => 'bg-danger-light',
            'text' => 'text-danger',
            'border' => 'border-danger/35',
            'icon' => 'heroicon-o-x-circle',
        ],
        default => [
            'label' => 'Chưa xác thực',
            'bg' => 'bg-slate-100',
            'text' => 'text-slate-500',
            'border' => 'border-slate-200',
            'icon' => 'heroicon-o-exclamation-triangle',
        ],
    };
@endphp

@if ($banner)
    @if ($status !== 'VERIFIED')
        <div
            class="flex items-center justify-between gap-3 rounded-2xl border {{ $config['border'] }} {{ $config['bg'] }} px-4 py-3 mb-4">
            <div class="flex items-center gap-2.5">
                <x-dynamic-component :component="$config['icon']" class="w-5 h-5 shrink-0 {{ $config['text'] }}" />
                <div>
                    <p class="{{ $config['text'] }} text-xs sm:text-sm font-black">
                        @if ($status === 'REJECTED')
                            Hồ sơ KYC bị từ chối{{ $reason ? ': ' . $reason : '' }}
                        @elseif($status === 'PENDING')
                            Hồ sơ KYC đang chờ duyệt
                        @else
                            Cần xác thực CCCD trước khi đăng tin
                        @endif
                    </p>
                </div>
            </div>
            @if ($status !== 'PENDING')
                <a href="{{ route('profile.kyc') }}"
                    class="shrink-0 text-[11px] font-black uppercase {{ $config['text'] }} underline whitespace-nowrap">
                    {{ $status === 'REJECTED' ? 'Nộp lại' : 'Xác thực ngay' }}
                </a>
            @endif
        </div>
    @endif
@else
    <div class="rounded-2xl border {{ $config['border'] }} {{ $config['bg'] }} px-4 py-3 flex items-start gap-3">
        <x-dynamic-component :component="$config['icon']" class="w-5 h-5 shrink-0 mt-0.5 {{ $config['text'] }}" />
        <div class="flex-1">
            <p class="{{ $config['text'] }} text-sm font-black">{{ $config['label'] }}</p>
            @if ($status === 'REJECTED' && $reason)
                <p class="text-slate-600 text-xs mt-1">Lý do: {{ $reason }}</p>
            @endif
        </div>
    </div>
@endif
