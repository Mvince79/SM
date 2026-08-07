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
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MissingChild extends Model
{
    use SoftDeletes;
    use LogsActivity;
    use HasFactory;

    protected $table = 'missing_children';

    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const EXPIRABLE_STATUSES = ['PENDING', 'APPROVED'];

    public const EXPIRATION_WARNING_DAYS = 30;

    public const RENEWAL_GRACE_DAYS = 30;

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
        'expires_at',
        'expiration_warning_sent_at',
        'archived_at',
        'status_before_archive',
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
    protected $guarded = ['status', 'approved_at', 'approved_by'];

    protected $casts = [
        'dob' => 'date',
        'missing_date' => 'date',
        'approved_at' => 'datetime',
        'found_at' => 'datetime',
        'expires_at' => 'datetime',
        'expiration_warning_sent_at' => 'datetime',
        'archived_at' => 'datetime',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];
    protected static function booted(): void
    {
        static::creating(function (MissingChild $missingChild): void {
            if (is_null($missingChild->expires_at)) {
                $missingChild->expires_at = now()->addYears(2);
            }
        });

        static::saving(function (MissingChild $missingChild) {
            if (blank($missingChild->slug)) {
                $missingChild->slug = static::generateUniqueSlug($missingChild);
            }
        });
    }

    public function canBeRenewed(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        $withinRenewalWindow = $this->expires_at
            ->copy()
            ->addDays(self::RENEWAL_GRACE_DAYS)
            ->greaterThanOrEqualTo(now());

        return $withinRenewalWindow
            && (in_array($this->status, self::EXPIRABLE_STATUSES, true)
                || $this->status === self::STATUS_ARCHIVED);
    }

    public function renewExpiration(): void
    {
        $status = $this->status === self::STATUS_ARCHIVED
            ? ($this->status_before_archive ?: 'APPROVED')
            : $this->status;

        $this->forceFill([
            'status' => $status,
            'expires_at' => now()->addYears(2),
            'expiration_warning_sent_at' => null,
            'archived_at' => null,
            'status_before_archive' => null,
        ])->save();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status']) // chỉ log field quan trọng, không log hết
            ->logOnlyDirty();     // chỉ log khi giá trị thực sự thay đổi
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
     * URL ảnh thumbnail cho lưới/card (list, home, search results).
     * LUÔN ưu tiên path_thumb (200x200, ~85 quality) bất kể quyền xem của user —
     * khác với trang chi tiết, grid không cần bản display/original nặng hơn.
     * Ưu tiên: path_thumb → path_display → path_blurred → path_original → DiceBear.
     */
    public function getThumbUrlAttribute(): string
    {
        $url = $this->resolveGridThumbUrl();

        if ($url) {
            return $url;
        }

        // Fallback: DiceBear avatar (không vỡ ảnh)
        $seed = urlencode($this->full_name);
        return "https://api.dicebear.com/7.x/thumbs/svg?seed={$seed}&backgroundColor=ffdfbf";
    }

    /**
     * URL bản WebP của thumbnail (dùng cho <picture><source type="image/webp">).
     * Trả về null nếu chưa có (ảnh cũ upload trước khi C-04 triển khai) — blade
     * component tự bỏ qua <source> và dùng img src JPG như bình thường.
     */
    public function getThumbWebpUrlAttribute(): ?string
    {
        $photo = $this->primaryPhotoForGrid();

        if ($photo && $photo->path_thumb_webp && Storage::disk('public')->exists($photo->path_thumb_webp)) {
            return asset('storage/' . $photo->path_thumb_webp);
        }

        return null;
    }

    private function resolveGridThumbUrl(): ?string
    {
        $photo = $this->primaryPhotoForGrid();

        if (!$photo) {
            return null;
        }

        foreach (['path_thumb', 'path_display', 'path_blurred', 'path_original'] as $field) {
            $path = $photo->{$field} ?? null;
            if ($path && Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
        }

        return null;
    }

    private function primaryPhotoForGrid(): ?Photo
    {
        $photo = $this->relationLoaded('primaryPhoto')
            ? $this->getRelation('primaryPhoto')
            : $this->primaryPhoto()->first();

        if (!$photo) {
            $photo = $this->relationLoaded('photos')
                ? $this->photos->first()
                : $this->photos()->orderBy('sort_order')->first();
        }

        return $photo;
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
            // path_display (resize ~1600px, nén ~80% quality) ưu tiên trước path_original
            // (full-res) để giảm băng thông — path_original chỉ dùng làm fallback cho
            // các ảnh cũ upload trước khi C-04 triển khai, hoặc khi job chưa xử lý xong.
            $fields = ['path_display', 'path_original', 'path_thumb', 'path_blurred'];
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
