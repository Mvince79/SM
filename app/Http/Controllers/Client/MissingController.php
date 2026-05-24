<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchMissingChildRequest;
use App\Models\MissingChild;
use App\Services\MissingChildSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class MissingController extends Controller
{
    protected MissingChildSearchService $searchService;

    public function __construct(MissingChildSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Trang danh sách: FULLTEXT search + filters
     *
     * Query params:
     * - q: keyword (FULLTEXT MATCH AGAINST)
     * - province_id: tỉnh
     * - gender: MALE|FEMALE|UNKNOWN
     * - age_min, age_max: range tuổi
     * - missing_date_from, missing_date_to: range ngày
     * - lat, lng, radius_km: tìm gần tôi (Haversine)
     * - sort: newest|oldest|near_me
     * - page: trang
     */
    public function index(SearchMissingChildRequest $request)
    {
        $filters = $request->validated();

        // Xử lý age_range → age_min, age_max
        if (!empty($filters['age_range'])) {
            [$min, $max] = explode('-', $filters['age_range']);
            $filters['age_min'] = (int) $min;
            $filters['age_max'] = (int) $max;
        }

        $cases = $this->searchService->search($filters);
        $provinces = \App\Models\Province::orderBy('name')->get();

        return view('missing.index', compact('cases', 'provinces', 'filters'));
    }

    /**
     * Trang chi tiết hồ sơ
     *
     * - Tăng view_count (Redis counter)
     * - Blur path_original cho guest
     * - Hiển thị QR code
     */
    public function detail(Request $request, int $id)
    {
        $missing = MissingChild::findOrFail($id);

        if ($missing->status !== 'APPROVED' && !Auth::check()) {
            abort(403, 'Hồ sơ này không được công khai');
        }

        return view('missing.detail', [
            'case' => $missing->load('user', 'province', 'primaryPhoto', 'photos')
        ]);
    }

    /**
     * Tăng view_count qua Redis (batch update mỗi 5 phút)
     * TODO: implement sau ở T-16
     */
    private function incrementViewCount(MissingChild $missing)
    {
        // TODO: $counter = Cache::increment("views:{$missing->id}");
        // TODO: if ($counter % 10 === 0) { $missing->increment('view_count', 10); }
    }
}
