<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Missing\SearchMissingRequest;
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
}