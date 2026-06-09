<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Jobs\TriggerFaceMatchJob;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FaceMatchController extends Controller
{
    public function trigger(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo_id' => ['required', 'integer', 'exists:photos,id'],
        ]);

        $photo = Photo::query()->findOrFail($data['photo_id']);
        $jobId = (string) Str::uuid();
        TriggerFaceMatchJob::dispatch($photo->id, $jobId);

        return response()->json([
            'queued' => true,
            'job_id' => $jobId,
            'photo_id' => $photo->id,
            'queue' => config('face_match.queue'),
        ]);
    }
}
