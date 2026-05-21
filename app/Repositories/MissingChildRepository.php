<?php

namespace App\Repositories;

use App\Models\MissingChild;
use Illuminate\Pagination\LengthAwarePaginator;

class MissingChildRepository
{
    public function searchPublic(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $q = $filters['q'] ?? '';
        $province = $filters['province'] ?? '';
        $gender = $filters['gender'] ?? '';
        $age = $filters['age'] ?? '';

        return MissingChild::whereIn('status', ['APPROVED', 'FOUND'])
            ->with(['primaryPhoto', 'province'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('full_name', 'like', "%{$q}%")
                        ->orWhere('special_marks', 'like', "%{$q}%")
                        ->orWhere('clothes_desc', 'like', "%{$q}%")
                        ->orWhere('district', 'like', "%{$q}%")
                        ->orWhereHas('province', fn($p) => $p->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($province, fn($q) => $q->where('province_id', $province))
            ->when($gender, fn($q) => $q->where('gender', $gender))
            ->when($age, function ($query) use ($age) {
                match ($age) {
                    '0-5' => $query->where('age_estimate', '<', 5),
                    '5-10' => $query->whereBetween('age_estimate', [5, 10]),
                    '10-15' => $query->whereBetween('age_estimate', [10, 15]),
                    '15+' => $query->where('age_estimate', '>', 15),
                    default => null,
                };
            })
            ->latest('approved_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findPublicById(int $id): MissingChild
    {
        return MissingChild::whereIn('status', ['APPROVED', 'FOUND'])
            ->with(['photos', 'province', 'clues.user'])
            ->findOrFail($id);
    }
}
