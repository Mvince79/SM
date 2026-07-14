<?php

namespace App\Livewire\Volunteer;

use App\Models\Clue;
use App\Models\VolunteerAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class AssignmentBoard extends Component
{
    public ?float $lat = null;

    public ?float $lng = null;

    public float $radius = 10;

    public array $nearbyClues = [];

    public array $myAssignments = [];

    public ?int $selectedAssignmentId = null;

    public string $resultStatus = '';

    public string $notes = '';

    public bool $locationLoaded = false;

    public function mount(): void
    {
        Gate::authorize('access-volunteer-features');

        $this->loadMyAssignments();
    }

    /**
     * Nhận vị trí từ trình duyệt.
     */
    public function setLocation(float $lat, float $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;
        $this->locationLoaded = true;

        $this->loadNearbyClues();
    }

    /**
     * Tải các manh mối trong bán kính đã chọn.
     */
    public function loadNearbyClues(): void
    {
        Gate::authorize('access-volunteer-features');

        $this->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'numeric', 'min:0.1', 'max:200'],
        ]);

        $originLat = (float) $this->lat;
        $originLng = (float) $this->lng;
        $radius = (float) $this->radius;

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
                $distance = $this->distanceKm(
                    $originLat,
                    $originLng,
                    (float) $clue->lat,
                    (float) $clue->lng
                );

                return [
                    'id' => $clue->id,
                    'description' => $clue->description,
                    'lat' => (float) $clue->lat,
                    'lng' => (float) $clue->lng,
                    'distance_km' => round($distance, 2),
                    'created_at' => optional($clue->created_at)
                        ->format('d/m/Y H:i'),
                    'missing_child' => $clue->missingChild ? [
                        'id' => $clue->missingChild->id,
                        'case_code' => $clue->missingChild->case_code,
                        'full_name' => $clue->missingChild->full_name,
                        'status' => $clue->missingChild->status,
                    ] : null,
                ];
            })
            ->filter(
                fn (array $clue): bool =>
                $clue['distance_km'] <= $radius
            )
            ->sortBy('distance_km')
            ->values();

        $this->nearbyClues = $clues->all();
    }

    /**
     * Nhận một nhiệm vụ xác minh.
     */
    public function acceptAssignment(int $clueId): void
    {
        Gate::authorize('access-volunteer-features');

        try {
            DB::transaction(function () use ($clueId): void {
                $clue = Clue::query()
                    ->whereKey($clueId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((bool) $clue->is_verified) {
                    throw new \RuntimeException(
                        'Manh mối này đã được xác minh.'
                    );
                }

                $hasActiveAssignment = VolunteerAssignment::query()
                    ->where('clue_id', $clue->id)
                    ->where('status', 'ASSIGNED')
                    ->lockForUpdate()
                    ->exists();

                if ($hasActiveAssignment) {
                    throw new \RuntimeException(
                        'Manh mối này đã có tình nguyện viên nhận.'
                    );
                }

                VolunteerAssignment::query()->create([
                    'user_id' => auth()->id(),
                    'clue_id' => $clue->id,
                    'status' => 'ASSIGNED',
                    'assigned_at' => now(),
                ]);
            });

            session()->flash(
                'success',
                'Bạn đã nhận nhiệm vụ xác minh thành công.'
            );

            $this->loadMyAssignments();

            if ($this->locationLoaded) {
                $this->loadNearbyClues();
            }
        } catch (Throwable $exception) {
            report($exception);

            $this->addError(
                'assignment',
                $exception->getMessage()
            );
        }
    }

    /**
     * Mở form gửi kết quả cho một nhiệm vụ.
     */
    public function openResultForm(int $assignmentId): void
    {
        $assignment = VolunteerAssignment::query()
            ->whereKey($assignmentId)
            ->where('user_id', auth()->id())
            ->where('status', 'ASSIGNED')
            ->firstOrFail();

        $this->selectedAssignmentId = $assignment->id;
        $this->resultStatus = '';
        $this->notes = '';
        $this->resetValidation();
    }

    /**
     * Đóng form gửi kết quả.
     */
    public function closeResultForm(): void
    {
        $this->selectedAssignmentId = null;
        $this->resultStatus = '';
        $this->notes = '';
        $this->resetValidation();
    }

    /**
     * Gửi kết quả xác minh.
     */
    public function submitResult(): void
    {
        Gate::authorize('access-volunteer-features');

        $data = $this->validate([
            'selectedAssignmentId' => [
                'required',
                'integer',
                'exists:volunteer_assignments,id',
            ],
            'resultStatus' => [
                'required',
                Rule::in(['VERIFIED', 'COULD_NOT_VERIFY']),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'resultStatus.required' =>
                'Vui lòng chọn kết quả xác minh.',
            'resultStatus.in' =>
                'Kết quả xác minh không hợp lệ.',
            'notes.max' =>
                'Ghi chú không được vượt quá 2000 ký tự.',
        ]);

        try {
            DB::transaction(function () use ($data): void {
                $assignment = VolunteerAssignment::query()
                    ->whereKey($data['selectedAssignmentId'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($assignment->user_id !== auth()->id()) {
                    abort(403);
                }

                if ($assignment->status !== 'ASSIGNED') {
                    throw new \RuntimeException(
                        'Nhiệm vụ này đã được cập nhật trước đó.'
                    );
                }

                $assignment->forceFill([
                    'status' => $data['resultStatus'],
                    'notes' => $data['notes'] ?: null,
                    'completed_at' => now(),
                ])->save();

                if ($data['resultStatus'] === 'VERIFIED') {
                    $assignment->clue()->update([
                        'is_verified' => true,
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                    ]);
                }
            });

            session()->flash(
                'success',
                'Đã gửi kết quả xác minh.'
            );

            $this->closeResultForm();
            $this->loadMyAssignments();

            if ($this->locationLoaded) {
                $this->loadNearbyClues();
            }
        } catch (Throwable $exception) {
            report($exception);

            $this->addError(
                'result',
                $exception->getMessage()
            );
        }
    }

    /**
     * Tải danh sách nhiệm vụ của người đang đăng nhập.
     */
    private function loadMyAssignments(): void
    {
        $this->myAssignments = VolunteerAssignment::query()
            ->with([
                'clue.missingChild:id,case_code,full_name,status',
            ])
            ->where('user_id', auth()->id())
            ->latest('assigned_at')
            ->get()
            ->map(function (VolunteerAssignment $assignment): array {
                return [
                    'id' => $assignment->id,
                    'clue_id' => $assignment->clue_id,
                    'status' => $assignment->status,
                    'notes' => $assignment->notes,
                    'assigned_at' => optional($assignment->assigned_at)
                        ->format('d/m/Y H:i'),
                    'completed_at' => optional($assignment->completed_at)
                        ->format('d/m/Y H:i'),
                    'clue' => $assignment->clue ? [
                        'description' =>
                            $assignment->clue->description,
                        'missing_child' =>
                            $assignment->clue->missingChild ? [
                                'case_code' =>
                                    $assignment->clue
                                        ->missingChild
                                        ->case_code,
                                'full_name' =>
                                    $assignment->clue
                                        ->missingChild
                                        ->full_name,
                            ] : null,
                    ] : null,
                ];
            })
            ->all();
    }

    private function distanceKm(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng
    ): float {
        $earthRadius = 6371;

        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat))
            * cos(deg2rad($toLat))
            * sin($lngDelta / 2) ** 2;

        return $earthRadius
            * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public function render()
    {
        return view('livewire.volunteer.assignment-board');
    }
}