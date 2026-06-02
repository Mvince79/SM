<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MissingChild;
use App\Http\Requests\Missing\SearchMissingRequest;
use App\Http\Requests\Missing\StoreMissingChildRequest;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\MissingChildService;

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

        return view('missing.detail', $data);
    }
}