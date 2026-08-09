<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Clue;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Jobs\ProcessPhotoJob;
use App\Events\NewClueSubmitted;
use App\Notifications\ClueSubmittedNotification;
use Illuminate\Support\Facades\Notification;
use App\Traits\HasIdempotency;
use App\Rules\SecureImage;

/**
 * API controller for handling Clue submissions from mobile/Postman.
 *
 * Provides a `store` method to validate input, save a `Clue`, optionally
 * handle image upload, create a `Photo` record and dispatch processing,
 * then notify the owner of the missing child.
 */
class ClueApiController extends Controller
{
    use HasIdempotency;
    public function store(Request $request)
    {
        // Ưu tiên lấy key từ header, nếu không có thì lấy từ request body.
        $request->merge([
            'idempotency_key' => $request->header('X-Idempotency-Key')
                ?: $request->input('idempotency_key'),
        ]);

        $data = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'missing_id' => ['required', 'exists:missing_children,id'],
            'description' => ['required', 'string', 'max:2000'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'is_anonymous' => ['boolean'],
            'image' => ['nullable', 'image', 'max:5120', new SecureImage],
        ]);

        $userId = Auth::id();

        if (!empty($data['is_anonymous'])) {
            $userId = null;
        }

        $requestKey = $data['idempotency_key'];

        $result = $this->resolveIdempotent(
            $requestKey,
            Clue::class,
            $userId,
            function () use ($data, $request, $userId) {
                $imagePath = null;

                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')
                        ->store('clues', 'public');
                }

                $clue = Clue::create([
                    'missing_id' => $data['missing_id'],
                    'user_id' => $userId,
                    'clue_type' => 'OTHER',
                    'description' => $data['description'],
                    'lat' => $data['lat'] ?? null,
                    'lng' => $data['lng'] ?? null,
                    'gps_shared_at' => isset($data['lat'], $data['lng'])
                        ? now()
                        : null,
                    'image_path' => $imagePath,
                    'is_anonymous' => !empty($data['is_anonymous']),
                ]);

                // Nếu có ảnh, tạo Photo và chạy job xử lý ảnh.
                if ($imagePath) {
                    try {
                        $photo = Photo::create([
                            'model_type' => Clue::class,
                            'model_id' => $clue->id,
                            'path_original' => $imagePath,
                            'file_size' => Storage::disk('public')->size($imagePath),
                            'mime_type' => Storage::disk('public')->mimeType($imagePath),
                        ]);

                        ProcessPhotoJob::dispatch($photo->id);
                    } catch (\Throwable $e) {
                        // Không làm hỏng request nếu xử lý ảnh thất bại.
                    }
                }

                // Thông báo cho chủ hồ sơ trẻ mất tích.
                try {
                    $missing = MissingChild::find($data['missing_id']);

                    if ($missing && $missing->user) {
                        Notification::send(
                            $missing->user,
                            new ClueSubmittedNotification($clue)
                        );

                        NewClueSubmitted::dispatch($clue);
                    }
                } catch (\Throwable $e) {
                    // Không làm hỏng request nếu gửi thông báo thất bại.
                }

                return $clue;
            }
        );

        // Khi request bị gửi lại, Trait có thể chỉ trả resource_id.
        $clue = $result['resource']
            ?? Clue::findOrFail($result['resource_id']);

        return response()->json([
            'data' => $clue,
        ], 201);
    }
}
