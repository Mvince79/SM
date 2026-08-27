<?php

namespace App\Livewire\Missing;

use App\Models\MissingChild;
use App\Repositories\HandoverPointRepository;
use App\Services\HandoverPointFinderService;
use Livewire\Component;

class HandoverSuggestionCard extends Component
{
    public int $caseId;

    /**
     * Số điểm bàn giao gợi ý tối đa hiển thị trong card.
     */
    private const SUGGESTION_LIMIT = 3;

    public function render(HandoverPointFinderService $finder, HandoverPointRepository $repository)
    {
        $case = MissingChild::query()
            ->with([
                'faceMatchResults' => fn($q) => $q->where('status', 'CONFIRMED')->with('foundChild'),
            ])
            ->find($this->caseId);

        $points = collect();

        if ($case) {
            // V-03 — Toạ độ neo để gợi ý điểm tiếp nhận lấy từ trẻ ĐƯỢC TÌM THẤY
            // (found_children), không dùng toạ độ nơi mất tích.
            $anchor = $this->resolveAnchorFromFoundChild($case);

            if ($anchor) {
                // nearestTo() đã tự lọc active() + cache theo toạ độ + trả kèm cột "distance" (km).
                $points = $finder->findNearest(
                    $anchor['lat'],
                    $anchor['lng'],
                    self::SUGGESTION_LIMIT,
                );
            } else {
                // Không có toạ độ trẻ tìm thấy → fallback theo tỉnh (KHÔNG gọi nearestTo
                // bằng toạ độ missing_children), ưu tiên Tổng đài 111 / Công an.
                $candidates = $case->province_id
                    ? $repository->byProvince((int) $case->province_id)
                    : $repository->all();

                $points = $candidates
                    ->whereIn('type', ['HOTLINE_111', 'POLICE_STATION'])
                    ->take(self::SUGGESTION_LIMIT)
                    ->values();
            }
        }

        return view('livewire.missing.handover-suggestion-card', [
            'points' => $points,
        ]);
    }

    /**
     * V-03 — Trả về toạ độ của trẻ được tìm thấy (khớp xác nhận) để làm điểm neo
     * gợi ý điểm tiếp nhận. null nếu chưa có trẻ tìm thấy nào có toạ độ.
     */
    private function resolveAnchorFromFoundChild(MissingChild $case): ?array
    {
        foreach ($case->faceMatchResults as $match) {
            $found = $match->foundChild;

            if ($found && is_numeric($found->lat) && is_numeric($found->lng)) {
                return ['lat' => (float) $found->lat, 'lng' => (float) $found->lng];
            }
        }

        return null;
    }
}