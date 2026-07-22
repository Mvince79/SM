<?php
use Livewire\Component;
use App\Models\MissingChild;
use App\Models\FoundChild;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public function getProvinceIdProperty(): ?int
    {
        return Auth::user()->thirdPartyVerifierProfile?->handoverPoint?->province_id;
    }

    public function getMissingCasesProperty()
    {
        if (! $this->provinceId) return collect();

        return MissingChild::where('province_id', $this->provinceId)
            ->where('status', 'APPROVED')
            ->whereNull('handover_confirmed_at')
            ->latest()
            ->get();
    }

    public function getFoundCasesProperty()
    {
        if (! $this->provinceId) return collect();

        return FoundChild::where('province_id', $this->provinceId)
            ->where('status', 'APPROVED')
            ->whereNull('handover_confirmed_at')
            ->latest()
            ->get();
    }
};
?>
<div class="space-y-4">
    @if (!$this->provinceId)
        <p class="text-sm text-slate-400 text-center py-10">
            Tài khoản của bạn chưa được gán vào điểm bàn giao nào. Liên hệ quản trị viên để được cấu hình.
        </p>
    @else
        @foreach ($this->missingCases->merge($this->foundCases) as $case)
            <div wire:key="case-{{ get_class($case) }}-{{ $case->id }}"
                class="bg-white border border-slate-200/85 rounded-2xl p-5 flex items-center justify-between">
                <div>
                    <p class="font-black text-slate-900 text-sm">{{ $case->full_name ?? $case->case_code }}</p>
                    <p class="text-xs text-slate-400">{{ $case->case_code }}</p>
                </div>
                <form method="POST"
                    action="{{ route('verifier.handover.confirm', ['handoverable' => $case->id, 'type' => $case instanceof \App\Models\MissingChild ? 'missing' : 'found']) }}">
                    @csrf
                    <button type="submit"
                        class="bg-primary text-white text-xs font-black px-4 py-2.5 rounded-xl flex items-center gap-1.5">
                        <x-heroicon-o-check-circle class="w-4 h-4" /> Xác nhận đã tiếp nhận trẻ
                    </button>
                </form>
            </div>
        @endforeach

        @if ($this->missingCases->isEmpty() && $this->foundCases->isEmpty())
            <p class="text-sm text-slate-400 text-center py-10">Không có hồ sơ nào cần xác nhận.</p>
        @endif
    @endif
</div>