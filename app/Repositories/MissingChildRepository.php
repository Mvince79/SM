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
        $province = $filters['province'] ?? '';
        $gender = $filters['gender'] ?? '';
        $age = $filters['age'] ?? '';
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
            ->when($age, function ($query) use ($age) {
                match ($age) {
                    '0-5' => $query->where('age_estimate', '<', 5),
                    '5-10' => $query->whereBetween('age_estimate', [5, 10]),
                    '10-15' => $query->whereBetween('age_estimate', [10, 15]),
                    '15+' => $query->where('age_estimate', '>', 15),
                    default => null,
                };
            });

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
        $query = MissingChild::with(['photos', 'primaryPhoto', 'province', 'clues.user']);

        $authId = Auth::user()->id ?? null;
        if ($authId) {
            $query->where(function ($q) use ($authId) {
                $q->whereIn('status', ['APPROVED', 'FOUND'])
                    ->orWhere('user_id', $authId);
            });
        } else {
            $query->whereIn('status', ['APPROVED', 'FOUND']);
        }

        return $query->findOrFail($id);
    }
}