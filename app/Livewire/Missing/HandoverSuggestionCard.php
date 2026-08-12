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
        $case = MissingChild::query()->find($this->caseId);

        $points = collect();

        if ($case) {
            $hasCaseCoordinates = is_numeric($case->lat) && is_numeric($case->lng);

            if ($hasCaseCoordinates) {
                // nearestTo() đã tự lọc active() + cache theo toạ độ + trả kèm cột "distance" (km).
                $points = $finder->findNearest(
                    (float) $case->lat,
                    (float) $case->lng,
                    self::SUGGESTION_LIMIT,
                );
            } else {
                // Không có toạ độ → dùng lại byProvince()/all() có sẵn trong repository (đã active() +
                // orderBy priority_order), rồi lọc riêng ưu tiên Tổng đài 111 / Công an ở tầng Collection.
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
}