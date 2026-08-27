<?php
use Livewire\Component;
use App\Models\FaceMatchResult;

new class extends Component
{
    public int $caseId;

    public function getConfirmedMatchProperty()
    {
        return FaceMatchResult::where('missing_id', $this->caseId)
            ->where('status', 'CONFIRMED')
            ->latest('confirmed_at')
            ->first();
    }

    public function getHandoverPointsProperty()
    {
        return collect([
            ['name' => 'Công an Phường 1', 'distance_km' => 1.2, 'hotline' => '113', 'lat' => 10.762, 'lng' => 106.660],
            ['name' => 'Trung tâm CTXH Bến Nghé', 'distance_km' => 2.5, 'hotline' => '1800-1234', 'lat' => 10.776, 'lng' => 106.700],
            ['name' => 'Bệnh viện Nhi Đồng 1', 'distance_km' => 3.8, 'hotline' => '028-3927-1119', 'lat' => 10.756, 'lng' => 106.669],
        ]);
    }
};
?>

<div>
    @if ($this->confirmedMatch)
        <x-handover-warning-banner />

        <div class="bg-white border border-slate-200/85 rounded-2xl p-5 space-y-3 shadow-premium">
            <div class="flex items-center gap-2">
                <x-heroicon-o-map-pin class="w-5 h-5 text-primary shrink-0" />
                <h3 class="font-black text-slate-900 text-sm">Gợi ý điểm bàn giao gần nhất</h3>
            </div>

            <div class="space-y-2">
                @foreach ($this->handoverPoints as $point)
                    <div class="flex items-center justify-between gap-3 bg-slate-50 rounded-xl p-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-800 truncate">{{ $point['name'] }}</p>
                            <p class="text-[10px] text-slate-400 font-semibold flex items-center gap-1">
                                {{ $point['distance_km'] }} km ·
                                <x-heroicon-o-phone class="w-3 h-3" />
                                <a href="tel:{{ $point['hotline'] }}" class="hover:text-primary">{{ $point['hotline'] }}</a>
                            </p>
                        </div>
                        <a href="#"
                            @click.prevent="window.open('https://www.google.com/maps?q={{ $point['lat'] }},{{ $point['lng'] }}', '_blank')"
                            class="shrink-0 text-[10px] font-black text-primary bg-primary/10 px-3 py-1.5 rounded-full hover:bg-primary/20 flex items-center gap-1">
                            <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" /> Chỉ đường
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>