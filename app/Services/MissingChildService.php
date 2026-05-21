<?php

namespace App\Services;

use App\Models\Province;
use App\Repositories\MissingChildRepository;

class MissingChildService
{
    public function __construct(
        private MissingChildRepository $missingChildRepository
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

        $case->increment('view_count');

        return compact('case');
    }
}
