<?php

use App\Events\ClueLocationUpdated;
use App\Models\Clue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $missingId;

    #[Locked]
    public ?int $clueId = null;

    public bool $tracking = false;

    public function mount(int $missingId): void
    {
        $this->missingId = $missingId;
    }

    public function startTracking(
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
    ): array {
        $this->ensureAuthenticated();
        $this->validateCoordinates($latitude, $longitude, $accuracy);

        $clue = Clue::create([
            'missing_id' => $this->missingId,
            'user_id' => Auth::id(),
            'clue_type' => 'SIGHTING',
            'description' => 'Người dùng đang chia sẻ vị trí GPS realtime.',
            'lat' => $latitude,
            'lng' => $longitude,
            'location_accuracy_m' => $accuracy,
            'gps_shared_at' => now(),
            'occurred_at' => now(),
            'is_verified' => false,
            'is_anonymous' => false,
            'is_hidden' => false,
        ]);

        $this->clueId = $clue->id;
        $this->tracking = true;

        broadcast(new ClueLocationUpdated($clue))->toOthers();

        return [
            'clue_id' => $clue->id,
            'lat' => (float) $clue->lat,
            'lng' => (float) $clue->lng,
        ];
    }

    public function updatePosition(
        int $clueId,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
    ): array {
        $this->ensureAuthenticated();
        $this->validateCoordinates($latitude, $longitude, $accuracy);

        $clue = Clue::query()
            ->whereKey($clueId)
            ->where('missing_id', $this->missingId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $clue->update([
            'lat' => $latitude,
            'lng' => $longitude,
            'location_accuracy_m' => $accuracy,
            'gps_shared_at' => now(),
        ]);

        broadcast(new ClueLocationUpdated($clue->fresh()))->toOthers();

        $this->clueId = $clue->id;
        $this->tracking = true;

        return [
            'clue_id' => $clue->id,
            'lat' => (float) $clue->lat,
            'lng' => (float) $clue->lng,
        ];
    }

    public function stopTracking(): void
    {
        $this->tracking = false;
    }

    private function ensureAuthenticated(): void
    {
        if (! Auth::check()) {
            throw ValidationException::withMessages([
                'gps' => 'Bạn cần đăng nhập để chia sẻ vị trí GPS.',
            ]);
        }
    }

    private function validateCoordinates(
        float $latitude,
        float $longitude,
        ?float $accuracy,
    ): void {
        validator(
            [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
            ],
            [
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'accuracy' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            ],
        )->validate();
    }
};
?>

<div
    x-data="gpsRealtime($wire)"
    x-init="init()"
    class="space-y-3"
>
    <button
        type="button"
        x-on:click="isTracking ? stopTracking() : startTracking()"
        x-bind:disabled="isSending"
        class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 font-bold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
        x-bind:class="isTracking
            ? 'bg-slate-700 hover:bg-slate-800'
            : 'bg-orange-500 hover:bg-orange-600'"
    >
        <span
            class="inline-block h-3 w-3 rounded-full"
            x-bind:class="isTracking
                ? 'animate-pulse bg-orange-300'
                : 'bg-white'"
        ></span>

        <span x-text="isTracking
            ? 'Dừng chia sẻ vị trí'
            : 'Tôi đang gặp trẻ lạc'">
        </span>
    </button>

    <p
        class="text-sm font-medium text-slate-600"
        x-text="status"
    ></p>

    <p
        x-show="errorMessage"
        x-text="errorMessage"
        class="text-sm font-semibold text-red-600"
    ></p>

    <div
        x-ref="gpsMap"
        wire:ignore
        class="h-64 w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-100"
    ></div>

    <style>
        .gps-live-marker-wrapper {
            background: transparent;
            border: 0;
        }

        .gps-live-marker {
            display: block;
            width: 20px;
            height: 20px;
            border: 4px solid #ffffff;
            border-radius: 9999px;
            background: #f97316;
            box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.75);
            animation: gps-live-pulse 1.4s infinite;
        }

        @keyframes gps-live-pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.75);
            }

            70% {
                box-shadow: 0 0 0 16px rgba(249, 115, 22, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(249, 115, 22, 0);
            }
        }
    </style>
</div>