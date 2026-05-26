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

        return compact('cases', 'provinces', 'q');
    }

    public function getDetailForPublic(int $id): array
    {
        $case = $this->missingChildRepository->findPublicById($id);
        $this->missingChildViewCountService->hit($case->id);

        // Show immediate increment by adding any pending Redis increments
        $pending = $this->missingChildViewCountService->getPendingCount($case->id);
        if ($pending > 0) {
            $case->view_count = ($case->view_count ?? 0) + $pending;
        }

        return compact('case');
    }

    public function getDetailForPublicByCaseCode(string $caseCode): array
    {
        $case = $this->missingChildRepository->findPublicByCaseCode($caseCode);

        $case->increment('view_count');

        return compact('case');
    }
}
