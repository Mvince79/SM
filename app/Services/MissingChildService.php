<?php

namespace App\Services;

use App\Models\Province;
use App\Repositories\MissingChildRepository;
use App\Services\MissingChildViewCountService;

class MissingChildService
{
    public function __construct(
        private MissingChildRepository $missingChildRepository,
        private MissingChildViewCountService $missingChildViewCountService
    ) {
    }

    public function getListForPublic(array $filters): array
    {
        $cases = $this->missingChildRepository->searchPublic($filters);
        $provinces = Province::where('is_active', 1)->orderBy('name')->get();
        $q = $filters['q'] ?? '';

        return compact('cases', 'provinces', 'q', 'filters');
    }

    public function getDetailForPublic(int $id): array
    {
        $case = $this->missingChildRepository->findPublicById($id);
        $this->missingChildViewCountService->hit($case->id);

        $pending = $this->missingChildViewCountService->getPendingCount($case->id);
        if ($pending > 0) {
            $case->view_count = ($case->view_count ?? 0) + $pending;
        }

        $bannerConfig = $this->buildBannerConfig($case);

        return compact('case', 'bannerConfig');
    }
    public function getDetailForPublicByCaseCode(string $caseCode): array
    {
        $case = $this->missingChildRepository->findPublicByCaseCode($caseCode);
        $case->increment('view_count');

        $bannerConfig = $this->buildBannerConfig($case);

        return compact('case', 'bannerConfig');
    }

    private function buildBannerConfig($case): array
    {
        if ($case->status === 'MATCHED') {
            return ['badge' => 'bg-green-500', 'badgeText' => 'Đã tìm thấy'];
        }

        if (!$case->missing_date) {
            return ['badge' => 'bg-slate-500', 'badgeText' => null];
        }

        $days = (int) -now()->diffInDays($case->missing_date);

        if ($days <= 3) {
            return ['badge' => 'bg-red-600', 'badgeText' => 'Mới mất tích'];
        }

        if ($days <= 30) {
            return ['badge' => 'bg-orange-500', 'badgeText' => null];
        }

        return ['badge' => 'bg-slate-500', 'badgeText' => null];
    }
}
