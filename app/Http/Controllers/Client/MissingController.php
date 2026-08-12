<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MissingChild;
use App\Http\Requests\Missing\SearchMissingRequest;
use App\Http\Requests\Missing\StoreMissingChildRequest;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\MissingChildService;
use App\Models\HandoverPoint;

class MissingController extends Controller
{
    public function __construct(
        private MissingChildService $missingChildService
    ) {
    }

    public function index(SearchMissingRequest $request)
    {
        $data = $this->missingChildService->getListForPublic($request->filters());

        return view('missing.index', $data);

    }

    public function detail(int $id)
    {
        $data = $this->missingChildService->getDetailForPublic($id);

        // Lấy danh sách HandoverPoint dựa theo province_id của trẻ
        $provinceId = $data['case']->province_id ?? $data['case']->last_seen_province_id ?? null;
        $data['handoverPoints'] = $this->getHandoverPointsByProvince($provinceId);

        return view('missing.detail', $data);
    }

    public function qr(int $id)
    {
        $case = MissingChild::query()->findOrFail($id);
        $detailUrl = route('missing.detail', ['id' => $case->id]);

        $svg = QrCode::format('svg')
            ->size(320)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($detailUrl);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function detailByCaseCode(string $caseCode)
    {
        $data = $this->missingChildService->getDetailForPublicByCaseCode($caseCode);

        return view('missing.detail', $data);
    }
    public function detailBySlug(string $slug)
    {
        $missingChild = MissingChild::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $this->missingChildService->getDetailForPublic($missingChild->id);

        // Lấy danh sách HandoverPoint
        $data['handoverPoints'] = $this->getHandoverPointsByProvince($missingChild->province_id ?? $missingChild->last_seen_province_id);

        return view('missing.detail', $data);
    }
    public function create()
    {
        return view('missing.create');
    }

    public function store(StoreMissingChildRequest $request)
    {
    }

    public function detailByQuery(\Illuminate\Http\Request $request)
    {
        if ($request->has('case_code')) {
            $data = $this->missingChildService->getDetailForPublicByCaseCode($request->case_code);
        } elseif ($request->has('id')) {
            $data = $this->missingChildService->getDetailForPublic((int) $request->id);
        } else {
            abort(404);
        }

        // Lấy danh sách HandoverPoint
        $provinceId = $data['case']->province_id ?? $data['case']->last_seen_province_id ?? null;
        $data['handoverPoints'] = $this->getHandoverPointsByProvince($provinceId);

        return view('missing.detail', $data);
    }

    public function resolvedShow(int $id)
    {
        $data = $this->missingChildService->getResolvedForPublic($id);

        return view('missing.resolved', $data);
    }
    /**
     * Hàm phụ trợ lấy HandoverPoint hoạt động theo Tỉnh/Thành
     */
    private function getHandoverPointsByProvince(?int $provinceId)
    {
        return HandoverPoint::where('is_active', true)
            ->when($provinceId, function ($query) use ($provinceId) {
                return $query->where('province_id', $provinceId);
            })
            ->orderBy('priority_order', 'asc')
            ->get();
    }
}
