<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\FaceMatchResult;
use App\Models\Clue;
use Illuminate\Support\Str;

class MissingChild extends Model
{
    use SoftDeletes;

    protected $table = 'missing_children';

    protected $fillable = [
        'user_id',
        'case_code',
        'full_name',
        'dob',
        'age_estimate',
        'gender',
        'skin_tone',
        'height_cm',
        'weight_kg',
        'province_id',
        'district',
        'ward',
        'address_detail',
        'lat',
        'lng',
        'missing_date',
        'missing_time',
        'clothes_desc',
        'clothes_color',
        'special_marks',
        'circumstances',
        'contact_name',
        'contact_phone',
        'contact_relation',
        'status',
        'approved_at',
        'approved_by',
        'found_at',
        'qr_code_path',
        'view_count',
        'share_count',
        'rejection_reason',
        'slug',
        'meta_title',
        'meta_description',
        'og_image_url',
        'handover_confirmed_at',
        'handover_confirmed_by',
    ];

    protected $casts = [
        'dob' => 'date',
        'missing_date' => 'date',
        'approved_at' => 'datetime',
        'found_at' => 'datetime',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];
    protected static function booted(): void
    {
        static::saving(function (MissingChild $missingChild) {
            if (blank($missingChild->slug)) {
                $missingChild->slug = static::generateUniqueSlug($missingChild);
            }
        });
    }

    protected static function generateUniqueSlug(MissingChild $missingChild): string
    {
        $baseSlug = Str::slug($missingChild->full_name) ?: 'missing-child';

        $caseCode = $missingChild->case_code
            ? strtolower($missingChild->case_code)
            : strtolower(Str::random(6));

        $slug = $baseSlug . '-' . $caseCode;
        $originalSlug = $slug;
        $counter = 2;

        while (
            static::where('slug', $slug)
                ->when($missingChild->exists, fn($query) => $query->where('id', '!=', $missingChild->id))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // Relationships
    public function clues()
    {
        return $this->hasMany(Clue::class, 'missing_id')->orderBy('created_at', 'desc');
    }

    public function photos()
    {
        return $this->morphMany(Photo::class, 'model');
    }

    public function faceMatchResults()
    {
        return $this->hasMany(FaceMatchResult::class, 'missing_id');
    }

    public function primaryPhoto()
    {
        return $this->morphOne(Photo::class, 'model')
            ->where('is_primary', 1)
            ->orderBy('sort_order');
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->whereNull('parent_id')
            ->where('is_approved', 1)
            ->with(['user', 'replies.user'])
            ->latest();
    }

    public function allComments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeFound($query)
    {
        return $query->where('status', 'FOUND');
    }

    // Helpers

    /**
     * Tính tuổi từ dob hoặc trả về age_estimate.
     */
    public function getAgeAttribute(): ?int
    {
        if ($this->dob) {
            return $this->dob->age;
        }
        return $this->age_estimate;
    }

    /**
     * Trả về missing_time format H:i nếu hợp lệ, null nếu không.
     * Tránh Carbon::parse() crash khi cột chứa giá trị text không phải time.
     */
    public function getFormattedTimeAttribute(): ?string
    {
        $t = $this->missing_time;
        if (!$t || !preg_match('/^\d{2}:\d{2}/', $t)) {
            return null;
        }
        try {
            $format = strlen($t) > 5 ? 'H:i:s' : 'H:i';
            return \Carbon\Carbon::createFromFormat($format, $t)->format('H:i');
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * URL ảnh thumbnail để hiển thị (không vỡ).
     * Ưu tiên: path_thumb → path_blurred → path_original → DiceBear placeholder.
     * path_thumb và path_blurred được tạo bởi ProcessPhotoJob.
     */
    public function getThumbUrlAttribute(): string
    {
        $url = $this->getDetailPrimaryPhotoUrlAttribute();

        if ($url) {
            return $url;
        }

        // Fallback: DiceBear avatar (không vỡ ảnh)
        $seed = urlencode($this->full_name);
        return "https://api.dicebear.com/7.x/thumbs/svg?seed={$seed}&backgroundColor=ffdfbf";
    }

    public function getDetailPhotoUrlsAttribute(): array
    {
        $authenticated = Auth::check();

        $photos = $this->relationLoaded('photos')
            ? $this->photos
            : $this->photos()->orderBy('sort_order')->get();

        return $photos->map(function (Photo $photo) use ($authenticated) {
            return $this->resolvePhotoUrlForViewer($photo, $authenticated);
        })->filter()->values()->all();
    }

    public function getDetailPrimaryPhotoUrlAttribute(): ?string
    {
        $authenticated = Auth::check();
        $photo = $this->relationLoaded('primaryPhoto')
            ? $this->getRelation('primaryPhoto')
            : $this->primaryPhoto()->first();

        if (!$photo) {
            $photo = $this->relationLoaded('photos')
                ? $this->photos->first()
                : $this->photos()->orderBy('sort_order')->first();
        }

        return $photo ? $this->resolvePhotoUrlForViewer($photo, $authenticated) : null;
    }

    public function getQrCodeUrlAttribute(): ?string
    {
        if ($this->qr_code_path && Storage::disk('public')->exists($this->qr_code_path)) {
            return asset('storage/' . $this->qr_code_path);
        }

        return route('missing.qr', ['id' => $this->id]);
    }

    protected function resolvePhotoUrlForViewer(Photo $photo, bool $authenticated): ?string
    {
        $user = Auth::user();

        if (!$user) {
            $fields = ['path_blurred'];
        } elseif ($user->isAdmin() || $user->hasVerifiedKyc()) {
            $fields = ['path_original', 'path_thumb', 'path_blurred'];
        } else {
            $fields = ['path_thumb', 'path_blurred'];
        }

        foreach ($fields as $field) {
            $path = $photo->{$field} ?? null;
            if ($path && Storage::disk('public')->exists($path)) {
                if ($field === 'path_original') {
                    $this->logOriginalPhotoAccess($photo, $user);
                }

                return asset('storage/' . $path);
            }
        }

        return null;
    }

    private function logOriginalPhotoAccess(Photo $photo, ?User $user): void
    {
        if (!$user || !function_exists('activity')) {
            return;
        }

        activity('photo_access')
            ->causedBy($user)
            ->performedOn($photo)
            ->withProperties([
                'photo_id' => $photo->id,
                'path_type' => 'path_original',
                'source' => 'missing_child_detail',
            ])
            ->log('Original photo accessed');
    }
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}
