<?php

namespace App\Repositories;

use App\Models\MissingChild;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class MissingChildRepository
{
    public function searchPublic(array $filters, int $perPage = 15, string $sort = 'latest'): LengthAwarePaginator
    {
        $q = $filters['q'] ?? '';
        $province = $filters['province_id'] ?? $filters['province'] ?? '';
        $gender = $filters['gender'] ?? '';
        $ageMin = $filters['age_min'] ?? null;
        $ageMax = $filters['age_max'] ?? null;
        $sort = $filters['sort'] ?? 'latest';
        $status = $filters['status'] ?? '';

        $query = MissingChild::with(['primaryPhoto', 'province'])
            ->when(
                $status,
                fn($q) => $q->where('status', $status),
                fn($q) => $q->whereIn('status', ['APPROVED', 'FOUND'])
            )
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
            ->when($ageMin, fn($q) => $q->where('age_estimate', '>=', (int) $ageMin))
            ->when($ageMax, fn($q) => $q->where('age_estimate', '<=', (int) $ageMax));

        if ($sort === 'oldest') {
            $query->oldest('approved_at');
        } elseif ($sort === 'urgent') {
            $query->orderByRaw("FIELD(status, 'APPROVED', 'FOUND')")->latest('approved_at');
        } else {
            $query->latest('approved_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findPublicById(int $id): MissingChild
    {
        $case = MissingChild::with(['photos', 'primaryPhoto', 'province', 'clues.user'])
            ->findOrFail($id);

        $authId = Auth::id();
        $canView = in_array($case->status, ['APPROVED', 'FOUND'])
            || ($authId && $case->user_id === $authId);

        if (!$canView) {
            abort(403);
        }

        return $case;
    }

    public function findPublicByCaseCode(string $caseCode): MissingChild
    {
        $case = MissingChild::with(['photos', 'primaryPhoto', 'province', 'clues.user'])
            ->where('case_code', $caseCode)
            ->firstOrFail();

        $authId = Auth::id();
        $canView = in_array($case->status, ['APPROVED', 'FOUND'])
            || ($authId && $case->user_id === $authId);

        if (!$canView) {
            abort(403);
        }

        return $case;
    }
}