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
        $total    = MissingChild::whereIn('status', ['APPROVED', 'FOUND', 'CLOSED'])->count();

        $this->stats = [
            'found'        => $found,
            'searching'    => $approved,
            'avg_time'     => '48h',
            'reunite_rate' => $total > 0 ? round($found / $total * 100) . '%' : '0%',
        ];
    }
};
?>

<section class="stats-bar">
    <div class="stats-bar-inner">
        <div class="stat-item">
            <div class="stat-num text-primary">{{ number_format($stats['found']) }}</div>
            <div class="stat-label">Con đã về nhà</div>
        </div>
        <div class="stat-item">
            <div class="stat-num text-slate-900">{{ number_format($stats['searching']) }}</div>
            <div class="stat-label">Đang tích cực tìm</div>
        </div>
        <div class="stat-item">
            <div class="stat-num text-brand">{{ $stats['avg_time'] }}</div>
            <div class="stat-label">Thời gian trung bình</div>
        </div>
        <div class="stat-item">
            <div class="stat-num text-success">{{ $stats['reunite_rate'] }}</div>
            <div class="stat-label">Tỷ lệ đoàn tụ</div>
        </div>
    </div>
</section>