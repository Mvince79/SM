<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoundChildRequest;
use App\Jobs\TriggerFaceMatchJob;
use App\Models\FoundChild;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FoundChildController extends Controller
{
    public function index(Request $request)
    {
        $q = FoundChild::with('province', 'user')->latest();
        $items = $q->paginate(12);
        return view('found_children.index', compact('items'));
    }

    public function create()
    {
        $provinces = \App\Models\Province::orderBy('name')->get();
        return view('found_children.create', compact('provinces'));
    }

    public function store(FoundChildRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $found = FoundChild::create($data);

        TriggerFaceMatchJob::dispatch($found->id);

        return redirect()->route('found-children.show', $found)->with('success', 'Đã tạo hồ sơ tìm được trẻ.');
    }

    public function show(FoundChild $foundChild)
    {
        return view('found_children.show', ['item' => $foundChild->load('province', 'user')]);
    }

    public function edit(FoundChild $foundChild)
    {
        Gate::authorize('update', $foundChild);
        $provinces = \App\Models\Province::orderBy('name')->get();
        return view('found_children.edit', compact('foundChild', 'provinces'));
    }

    public function update(FoundChildRequest $request, FoundChild $foundChild)
    {
        Gate::authorize('update', $foundChild);
        $foundChild->update($request->validated());
        return redirect()->route('found-children.show', $foundChild)->with('success', 'Cập nhật hồ sơ thành công.');
    }

    public function destroy(FoundChild $foundChild)
    {
        Gate::authorize('delete', $foundChild);
        $foundChild->delete();
        return redirect()->route('found-children.index')->with('success', 'Đã xóa hồ sơ.');
    }
}
