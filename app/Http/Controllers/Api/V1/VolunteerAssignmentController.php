<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Clue;
use App\Models\VolunteerAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VolunteerAssignmentController extends Controller
{
    public function nearbyClues(Request $request): JsonResponse
    {
        Gate::authorize('access-volunteer-features');

        $filters = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:0.1', 'max:200'],
        ]);

        $originLat = (float) $filters['lat'];
        $originLng = (float) $filters['lng'];
        $radius = (float) ($filters['radius'] ?? 10);

        $clues = Clue::query()
            ->with(['missingChild:id,case_code,full_name,status'])
            ->where('is_verified', false)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereDoesntHave('volunteerAssignments', function ($query) {
                $query->where('status', 'ASSIGNED');
            })
            ->latest()
            ->get()
            ->map(function (Clue $clue) use ($originLat, $originLng) {
                $clue->distance_km = round($this->distanceKm(
                    $originLat,
                    $originLng,
                    (float) $clue->lat,
                    (float) $clue->lng
                ), 2);

                return $clue;
            })
            ->filter(fn (Clue $clue) => $clue->distance_km <= $radius)
            ->sortBy('distance_km')
            ->values();

        return response()->json([
            'data' => $clues->map(fn (Clue $clue) => $this->cluePayload($clue))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('access-volunteer-features');

        $data = $request->validate([
            'clue_id' => ['required', 'exists:clues,id'],
        ]);

        $assignment = DB::transaction(function () use ($data) {
            $clue = Clue::query()
                ->whereKey($data['clue_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ((bool) $clue->is_verified) {
                abort(422, 'Manh moi nay da duoc xac minh.');
            }

            $hasActiveAssignment = VolunteerAssignment::query()
                ->where('clue_id', $clue->id)
                ->where('status', 'ASSIGNED')
                ->lockForUpdate()
                ->exists();

            if ($hasActiveAssignment) {
                abort(409, 'Manh moi nay da co tinh nguyen vien nhan xac minh.');
            }

            return VolunteerAssignment::query()->create([
                'user_id' => auth()->id(),
                'clue_id' => $clue->id,
                'status' => 'ASSIGNED',
                'assigned_at' => now(),
            ]);
        });

        return response()->json([
            'data' => $this->assignmentPayload($assignment->load('clue')),
        ], 201);
    }

    public function update(Request $request, VolunteerAssignment $assignment): JsonResponse
    {
        Gate::authorize('access-volunteer-features');

        abort_unless($assignment->user_id === auth()->id(), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['VERIFIED', 'COULD_NOT_VERIFY'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $assignment = DB::transaction(function () use ($assignment, $data) {
            $assignment = VolunteerAssignment::query()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($assignment->user_id === auth()->id(), 403);

            if ($assignment->status !== 'ASSIGNED') {
                abort(422, 'Nhiem vu nay da duoc cap nhat truoc do.');
            }

            $assignment->forceFill([
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'completed_at' => now(),
            ])->save();

            if ($data['status'] === 'VERIFIED') {
                $assignment->clue()->update([
                    'is_verified' => true,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);
            }

            return $assignment;
        });

        return response()->json([
            'data' => $this->assignmentPayload($assignment->load('clue')),
        ]);
    }

    private function cluePayload(Clue $clue): array
    {
        return [
            'id' => $clue->id,
            'missing_id' => $clue->missing_id,
            'description' => $clue->description,
            'lat' => $clue->lat !== null ? (float) $clue->lat : null,
            'lng' => $clue->lng !== null ? (float) $clue->lng : null,
            'distance_km' => $clue->distance_km ?? null,
            'created_at' => optional($clue->created_at)->toIso8601String(),
            'missing_child' => $clue->missingChild ? [
                'id' => $clue->missingChild->id,
                'case_code' => $clue->missingChild->case_code,
                'full_name' => $clue->missingChild->full_name,
                'status' => $clue->missingChild->status,
            ] : null,
        ];
    }

    private function assignmentPayload(VolunteerAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'clue_id' => $assignment->clue_id,
            'status' => $assignment->status,
            'notes' => $assignment->notes,
            'assigned_at' => optional($assignment->assigned_at)->toIso8601String(),
            'completed_at' => optional($assignment->completed_at)->toIso8601String(),
            'clue' => $assignment->relationLoaded('clue') && $assignment->clue
                ? $this->cluePayload($assignment->clue)
                : null,
        ];
    }

    private function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
