<?php

namespace App\Services;

use App\Models\MissingChild;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class MissingChildSearchService
{
    protected const CACHE_TTL = 300;
    protected const PER_PAGE = 15;

    public function search(array $filters = []): LengthAwarePaginator
    {
        $cacheKey = $this->getCacheKey($filters);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $query = $this->buildQuery($filters);
        $result = $query->paginate(
            self::PER_PAGE,
            ['*'],
            'page',
            $filters['page'] ?? 1
        );

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    // ✅ METHOD NÀY BỊ MẤT — thêm lại vào đây
    protected function buildQuery(array $filters): Builder
    {
        $query = MissingChild::query()
            ->where('status', 'APPROVED')
            ->whereNull('deleted_at');

        $hasKeyword = !empty($filters['q']);
        $hasLocation = !empty($filters['lat']) && !empty($filters['lng']);

        // 1. FULLTEXT search
        if ($hasKeyword) {
            $keyword = $this->escapeBooleanKeyword($filters['q']);
            $query->whereRaw(
                "MATCH(full_name, clothes_desc, special_marks, circumstances) AGAINST(? IN BOOLEAN MODE)",
                [$keyword]
            );
        }

        // 2. Filter tỉnh
        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        // 3. Filter giới tính
        if (!empty($filters['gender'])) {
            $query->where('gender', strtoupper($filters['gender']));
        }

        // 4. Filter tuổi
        if (isset($filters['age_min']) && $filters['age_min'] !== '') {
            $query->where('age_estimate', '>=', (int) $filters['age_min']);
        }
        if (isset($filters['age_max']) && $filters['age_max'] !== '') {
            $query->where('age_estimate', '<=', (int) $filters['age_max']);
        }

        // 5. Filter ngày mất tích
        if (!empty($filters['missing_date_from'])) {
            $query->whereDate('missing_date', '>=', $filters['missing_date_from']);
        }
        if (!empty($filters['missing_date_to'])) {
            $query->whereDate('missing_date', '<=', $filters['missing_date_to']);
        }

        // 6. SELECT + Haversine
        if ($hasLocation) {
            $lat = floatval($filters['lat']);
            $lng = floatval($filters['lng']);
            $radius = floatval($filters['radius_km'] ?? 50);
            $bbox = $this->getBoundingBox($lat, $lng, $radius);

            $query->whereBetween('lat', [$bbox['min_lat'], $bbox['max_lat']])
                ->whereBetween('lng', [$bbox['min_lng'], $bbox['max_lng']]);

            $selectParts = ['missing_children.*'];
            $bindings = [];

            $selectParts[] = "(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance_km";
            $bindings = array_merge($bindings, [$lat, $lng, $lat]);

            if ($hasKeyword) {
                $selectParts[] = "MATCH(full_name, clothes_desc, special_marks, circumstances) AGAINST(? IN BOOLEAN MODE) AS relevance";
                $bindings[] = $filters['q'];
            }

            $query->selectRaw(implode(', ', $selectParts), $bindings);
            $query->having('distance_km', '<=', $radius);

        } else {
            $selectParts = ['missing_children.*'];
            $bindings = [];

            if ($hasKeyword) {
                $selectParts[] = "MATCH(full_name, clothes_desc, special_marks, circumstances) AGAINST(? IN BOOLEAN MODE) AS relevance";
                $bindings[] = $filters['q'];
            }

            $query->selectRaw(implode(', ', $selectParts), $bindings);
        }

        // 7. Sort
        $sort = $filters['sort'] ?? 'newest';

        if ($sort === 'near_me' && $hasLocation) {
            $query->orderBy('distance_km', 'asc');
        } elseif ($sort === 'oldest') {
            $query->orderBy('missing_date', 'asc');
        } else {
            if ($hasKeyword) {
                $query->orderByRaw('relevance DESC');
            }
            $query->latest('missing_date');
        }

        // 8. Eager load
        $query->with(['province', 'primaryPhoto']);

        return $query;
    }

    protected function escapeBooleanKeyword(string $keyword): string
    {
        $keyword = trim($keyword);
        if (empty($keyword)) {
            return '*';
        }

        $keyword = str_replace(['\\', '"', "'"], ['\\\\', '\\"', "\\'"], $keyword);

        $words = preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY);
        $escaped = array_map(function ($word) {
            if (!preg_match('/^[+\-><@~"]/', $word)) {
                return "+{$word}*";
            }
            return $word;
        }, $words);

        return implode(' ', $escaped);
    }

    protected function getBoundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $latOffset = $radiusKm / 111.0;
        $lngOffset = $radiusKm / (111.0 * cos(deg2rad($lat)));

        return [
            'min_lat' => $lat - $latOffset,
            'max_lat' => $lat + $latOffset,
            'min_lng' => $lng - $lngOffset,
            'max_lng' => $lng + $lngOffset,
        ];
    }

    protected function getCacheKey(array $filters): string
    {
        $cacheFilters = array_filter($filters, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY);
        $hash = hash('sha256', json_encode($cacheFilters));
        return "missing_search:{$hash}";
    }

    public static function clearCache(): void
    {
        // Cache tự expire sau TTL 5 phút
    }
}