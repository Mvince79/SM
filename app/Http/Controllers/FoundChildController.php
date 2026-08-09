<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoundChildRequest;
use App\Models\FoundChild;
use App\Traits\HasIdempotency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Traits\HasRedisLock;
use App\Rules\SecureImage;

class FoundChildController extends Controller
{
    use HasIdempotency;
    use HasRedisLock;

    public function index(Request $request)
    {
        $q = FoundChild::with('province', 'user', 'photos')->latest();
        $items = $q->paginate(12);
        return view('found_children.index', compact('items'));
    }

    public function storePhoto(Request $request, FoundChild $foundChild)
    {
        $this->authorize('update', $foundChild);

        $request->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120', new SecureImage],
        ]);

        $setPrimary = (bool) $request->input('set_primary', false);

        $files = $request->file('photos', []);
        foreach ($files as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $path = $file->store('found_children/' . $foundChild->id . '/original', 'public');
            $imageSize = getimagesize($file->getPathname());
            $width = $imageSize[0] ?? null;
            $height = $imageSize[1] ?? null;

            if ($setPrimary) {
                // unset existing primary (only once)
                \App\Models\Photo::where('model_type', FoundChild::class)
                    ->where('model_id', $foundChild->id)
                    ->where('is_primary', 1)
                    ->update(['is_primary' => 0]);
                $isPrimary = 1;
                // After first file setPrimary should be false for subsequent files
                $setPrimary = false;
            } else {
                $isPrimary = 0;
            }

            \App\Models\Photo::create([
                'model_type' => FoundChild::class,
                'model_id' => $foundChild->id,
                'path_original' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'width' => $width,
                'height' => $height,
                'is_primary' => $isPrimary,
            ]);
        }

        return redirect()->route('found-children.show', $foundChild)->with('success', 'Ảnh đã được tải lên.');
    }

    public function create()
    {
        $provinces = \App\Models\Province::orderBy('name')->get();
        return view('found_children.create', compact('provinces'));
    }

    public function store(FoundChildRequest $request)
    {
        $this->authorize('create', FoundChild::class);

        $userId = auth()->id();
        $limiter = app(\App\Services\RateLimiterService::class);

        if ($limiter->tooManyFoundChildSubmissions($userId)) {
            $seconds = $limiter->foundChildAvailableIn($userId);
            return back()->withErrors([
                'limit' => "Bạn đã đăng quá nhiều tin hôm nay. Vui lòng thử lại sau " . ceil($seconds / 3600) . " giờ.",
            ]);
        }

        $data = $request->validated();
        $data['user_id'] = $userId;
        $data['status'] = 'PENDING';

        $requestKey = $request->header('X-Idempotency-Key')
            ?: $request->input('idempotency_key')
            ?: $this->generateIdempotencyKey();

        $result = $this->withSubmitLock('submit-found:' . $userId, function () use ($requestKey, $data, $request, $limiter, $userId) {
            return $this->resolveIdempotent($requestKey, FoundChild::class, $userId, function () use ($data, $request, $limiter, $userId) {
                $found = FoundChild::create($data);
                $limiter->hitFoundChildSubmission($userId);

                if ($request->hasFile('photos')) {
                    $files = $request->file('photos');
                    $first = true;
                    foreach ($files as $file) {
                        if (!$file->isValid())
                            continue;
                        $path = $file->store('found_children/' . $found->id . '/original', 'public');
                        $imageSize = getimagesize($file->getPathname());
                        $width = $imageSize[0] ?? null;
                        $height = $imageSize[1] ?? null;

                        \App\Models\Photo::create([
                            'model_type' => FoundChild::class,
                            'model_id' => $found->id,
                            'path_original' => $path,
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getClientMimeType(),
                            'width' => $width,
                            'height' => $height,
                            'is_primary' => $first ? 1 : 0,
                        ]);
                        $first = false;
                    }
                }

                return $found;
            });
        });

        return redirect()->route('found-children.show', $result['resource_id'])->with('success', 'Đã tạo hồ sơ tìm được trẻ.');
    }

    public function show(FoundChild $foundChild)
    {
        return view('found_children.show', [
            'item' => $foundChild->load([
                'province',
                'user',
                'photos' => fn($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            ]),
        ]);
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
        $data = $request->validated();
        $foundChild->update($data);

        // Handle optional uploaded photo from edit form
        if ($request->hasFile('photo')) {
            $request->validate([
                'photo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120', new SecureImage],
            ]);

            $file = $request->file('photo');
            $path = $file->store('found_children/' . $foundChild->id . '/original', 'public');

            $imageSize = getimagesize($file->getPathname());
            $width = $imageSize[0] ?? null;
            $height = $imageSize[1] ?? null;

            $setPrimary = (bool) $request->input('set_primary', false);
            if ($setPrimary) {
                \App\Models\Photo::where('model_type', FoundChild::class)
                    ->where('model_id', $foundChild->id)
                    ->where('is_primary', 1)
                    ->update(['is_primary' => 0]);
            }

            \App\Models\Photo::create([
                'model_type' => FoundChild::class,
                'model_id' => $foundChild->id,
                'path_original' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'width' => $width,
                'height' => $height,
                'is_primary' => $setPrimary ? 1 : 0,
            ]);
        }

        return redirect()->route('found-children.show', $foundChild)->with('success', 'Cập nhật hồ sơ thành công.');
    }

    public function destroy(FoundChild $foundChild)
    {
        Gate::authorize('delete', $foundChild);
        $foundChild->delete();
        return redirect()->route('found-children.index')->with('success', 'Đã xóa hồ sơ.');
    }
}
