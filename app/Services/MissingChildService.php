<?php

namespace App\Services;

use App\Models\Province;
use App\Repositories\MissingChildRepository;
use App\Services\MissingChildViewCountService;
use App\Models\FaceMatchResult;
use App\Models\MissingChild;
use Carbon\Carbon;

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
        // F-02 (T-34): gom "đã xác nhận" HOẶC "score >= ngưỡng" mà vẫn giữ điều kiện
        // missing_id ngầm định. Bọc OR trong closure để không phá khoá ngoại.
        $aiMatches = $case->faceMatchResults()
            ->where(function ($q) {
                $q->where('status', 'CONFIRMED')
                    ->orWhere('score', '>=', config('face_match.score_threshold', 0.7));
            })
            ->with([
                'foundChild.province',
                'foundChild.photos' => fn($q) => $q->orderBy('sort_order')->limit(1),
            ])
            ->orderBy('score', 'desc')
            ->take(3)
            ->get();

        $this->missingChildViewCountService->hit($case->id);

        $pending = $this->missingChildViewCountService->getPendingCount($case->id);
        if ($pending > 0) {
            $case->view_count = ($case->view_count ?? 0) + $pending;
        }

        $bannerConfig = $this->buildBannerConfig($case);

        return compact('case', 'bannerConfig', 'aiMatches');
    }
    public function getDetailForPublicByCaseCode(string $caseCode): array
    {
        $case = $this->missingChildRepository->findPublicByCaseCode($caseCode);
        // F-02 (T-34): gom "đã xác nhận" HOẶC "score >= ngưỡng" mà vẫn giữ điều kiện
        // missing_id ngầm định. Bọc OR trong closure để không phá khoá ngoại.
        $aiMatches = $case->faceMatchResults()
            ->where(function ($q) {
                $q->where('status', 'CONFIRMED')
                    ->orWhere('score', '>=', config('face_match.score_threshold', 0.7));
            })
            ->with([
                'foundChild.province',
                'foundChild.photos' => fn($q) => $q->orderBy('sort_order')->limit(1),
            ])
            ->orderBy('score', 'desc')
            ->take(3)
            ->get();

        $case->increment('view_count');

        $bannerConfig = $this->buildBannerConfig($case);

        return compact('case', 'bannerConfig', 'aiMatches');
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

    public function getResolvedForPublic(int $id): array
    {
        $case = MissingChild::with([
            'photos' => fn($q) => $q->orderBy('sort_order'),
            'primaryPhoto',
        ])->findOrFail($id);

        if (!in_array($case->status, ['FOUND', 'CLOSED'])) {
            abort(404);
        }

        $matchResult = FaceMatchResult::where('missing_id', $id)
            ->where('status', 'CONFIRMED')
            ->with([
                'foundChild.photos' => fn($q) => $q->orderBy('sort_order'),
                'foundChild.primaryPhoto',
            ])
            ->orderBy('score', 'desc')
            ->first();

        $foundChild = $matchResult?->foundChild;

        $daysCount = null;
        if ($case->missing_date && $case->found_at) {
            $daysCount = (int) Carbon::parse($case->missing_date)
                ->diffInDays(Carbon::parse($case->found_at));
        }

        $ogImage = $case->detail_primary_photo_url
            ?? $foundChild?->primary_photo_url
            ?? null;

        return compact('case', 'matchResult', 'foundChild', 'daysCount', 'ogImage');
    }
}
