<?php

namespace App\Http\Livewire\MissingChild;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Clue;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Jobs\ProcessPhotoJob;
use App\Events\NewClueSubmitted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ClueSubmittedNotification;

class ClueForm extends Component
{
    use WithFileUploads;

    public int $missingId;
    public string $description = '';
    public $image = null;
    public string $lat = '';
    public string $lng = '';
    public bool $is_anonymous = false;

    protected $listeners = ['setLocation'];

    public function mount($missingId)
    {
        $this->missingId = (int) $missingId;
    }

    protected function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'is_anonymous' => ['boolean'],
        ];
    }

    public function setLocation($data)
    {
        if (is_array($data)) {
            $this->lat = $data['lat'] ?? $this->lat;
            $this->lng = $data['lng'] ?? $this->lng;
        }
    }

    public function submit()
    {
        if (!Auth::check()) {
            session()->flash('clue_error', 'Bạn phải đăng nhập để gửi manh mối.');
            return;
        }

        $this->validate();

        // Always record the authenticated user id so admins can trace the sender.
        $userId = Auth::id();

        $imagePath = null;
        if ($this->image) {
            $imagePath = $this->image->store('clues', 'public');
        }

        $clue = Clue::create([
            'missing_id' => $this->missingId,
            'user_id' => $userId,
            'clue_type' => 'OTHER',
            'description' => $this->description,
            'lat' => $this->lat ?: null,
            'lng' => $this->lng ?: null,
            'gps_shared_at' => $this->lat && $this->lng ? now() : null,
            'image_path' => $imagePath,
            'is_anonymous' => $this->is_anonymous,
        ]);

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
                // ignore
            }
        }

        try {
            $missing = MissingChild::find($this->missingId);
            if ($missing && $missing->user) {
                Notification::send($missing->user, new ClueSubmittedNotification($clue));
                NewClueSubmitted::dispatch($clue);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        session()->flash('clue_submitted', 'Cảm ơn bạn đã gửi manh mối.');
        $this->reset(['description', 'image', 'lat', 'lng', 'is_anonymous']);
    }

    public function render()
    {
        return view('livewire.missing-child.clue-form');
    }
}
