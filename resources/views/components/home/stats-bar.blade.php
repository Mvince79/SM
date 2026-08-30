<?php

use Livewire\Component;
use App\Models\MissingChild;

new class extends Component
{
    public array $stats = [];

    public function mount(): void
    {
        $approved = MissingChild::where('status', 'APPROVED')->count();
        $found    = MissingChild::where('status', 'FOUND')->count();
        $reunited = MissingChild::where('status', MissingChild::STATUS_REUNITED)->count();

        $base = $approved + $found + $reunited;

        $this->stats = [
            'found'        => $found,
            'searching'    => $approved,
            'reunited'     => $reunited,
            'reunite_rate' => $base > 0 ? round($reunited / $base * 100) . '%' : '0%',
        ];
    }
};
?>

<section class="stats-bar">
    <div class="stats-bar-inner">
        <div class="stat-item">
            <div class="stat-num text-primary">{{ number_format($stats['found']) }}</div>
            <div class="stat-label">Đã tìm thấy</div>
        </div>
        <div class="stat-item">
            <div class="stat-num text-slate-900">{{ number_format($stats['searching']) }}</div>
            <div class="stat-label">Đang tích cực tìm</div>
        </div>
        <div class="stat-item">
            <div class="stat-num text-success">{{ number_format($stats['reunited']) }}</div>
            <div class="stat-label">Con đã về nhà</div>
        </div>
        <div class="stat-item">
            <div class="stat-num text-brand">{{ $stats['reunite_rate'] }}</div>
            <div class="stat-label">Tỷ lệ đoàn tụ</div>
        </div>
    </div>
</section>