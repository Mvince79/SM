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

        $query = MissingChild::with([
            'primaryPhoto:id,model_id,model_type,path_original,path_thumb,path_blurred,is_primary,sort_order',  // ✅ select columns cần thiết
            'province:id,name',                                       // ✅ chỉ lấy id,name
        ])
            ->withCount(['clues', 'comments'])                            // ✅ fix N+1 count
            ->when(
                $status,
                fn($q) => $q->where('status', $status),
                fn($q) => $q->activeForPublic()
            )
            ->when($q, function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('full_name', 'like', "%{$q}%")
                        ->orWhere('special_marks', 'like', "%{$q}%")
                        ->orWhere('clothes_desc', 'like', "%{$q}%")
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
        $case = MissingChild::with([
            'primaryPhoto:id,model_id,model_type,path_original,path_thumb,path_blurred,is_primary,sort_order',  // ✅ bỏ 'photos' thừa
            'province:id,name',
            'clues' => fn($q) => $q->where('is_hidden', false)      // ✅ chỉ load clues không bị ẩn
                ->with(['user:id,full_name'])             // ✅ chỉ lấy id,full_name
                ->latest(),
            'comments' => fn($q) => $q->with(['user:id,full_name']) // ✅ eager load comments
                ->latest(),
        ])
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
        $case = MissingChild::with([
            'primaryPhoto:id,model_id,model_type,path_original,path_thumb,path_blurred,is_primary,sort_order',
            'province:id,name',
            'clues' => fn($q) => $q->where('is_hidden', false)
                ->with(['user:id,full_name'])
                ->latest(),
            'comments' => fn($q) => $q->with(['user:id,full_name'])
                ->latest(),
        ])
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