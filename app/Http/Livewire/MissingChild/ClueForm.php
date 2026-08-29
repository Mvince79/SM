<?php

namespace App\Http\Livewire\MissingChild;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Clue;
use App\Models\MissingChild;
use App\Models\Photo;
use App\Jobs\ProcessPhotoJob;
use App\Events\NewClueSubmitted;
use App\Traits\HasIdempotency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Rules\SecureImage;

class ClueForm extends Component
{
    use WithFileUploads;
    use HasIdempotency;

    public int $missingId;
    public string $description = '';
    public $image = null;
    public string $lat = '';
    public string $lng = '';
    public bool $is_anonymous = false;

    // E-01c: key chống double-submit (double-click / network retry) — sinh 1 lần
    // khi mount, gắn vào hidden input trong view, và được sinh lại (mới) sau mỗi
    // lần submit thành công để lần gửi TIẾP THEO (một manh mối khác) có key riêng.
    public string $idempotencyKey = '';

    protected $listeners = ['setLocation'];

    public function mount($missingId)
    {
        $this->missingId = (int) $missingId;
        $this->idempotencyKey = $this->generateIdempotencyKey();
    }

    protected function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120', new SecureImage],
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

        // E-01c: bọc toàn bộ việc TẠO record (Clue + Photo đính kèm + dispatch job xử lý ảnh)
        // trong 1 DB::transaction() có kiểm tra idempotency key (trait HasIdempotency từ E-01a).
        // Nếu $this->idempotencyKey đã tồn tại trong bảng idempotency_keys (double-click nhanh,
        // Livewire request bị gửi lại do mất mạng, ...) -> callback KHÔNG chạy lại, không upload
        // ảnh trùng, không tạo Clue trùng -> trả thẳng bản ghi cũ (was_duplicate = true).
        $result = $this->resolveIdempotent(
            $this->idempotencyKey,
            'clues',
            $userId,
            function () use ($userId) {
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

                return $clue;
            }
        );

        // CLUE_SUBMITTED do ClueObserver phát khi clue được tạo (phủ mọi đường vào,
        // không chỉ form này). NewClueSubmitted giữ nguyên để realtime cho socket.
        // CHỈ chạy khi đây thực sự là Clue mới (was_duplicate === false) — tránh
        // gửi thông báo trùng cho lần submit thứ 2 (đúng DoD của E-01c).
        if (!$result['was_duplicate']) {
            $clue = $result['resource'];

            try {
                NewClueSubmitted::dispatch($clue);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        session()->flash('clue_submitted', 'Cảm ơn bạn đã gửi manh mối.');
        $this->reset(['description', 'image', 'lat', 'lng', 'is_anonymous']);

        // Sinh key MỚI cho lần gửi manh mối tiếp theo (nếu không, submit lần sau sẽ
        // bị nhận nhầm là duplicate của lần vừa rồi).
        $this->idempotencyKey = $this->generateIdempotencyKey();
    }

    public function render()
    {
        return view('livewire.missing-child.clue-form');
    }
}
