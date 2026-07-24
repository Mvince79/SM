<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * L-01 — Bài viết SEO.
 *
 * Slug tự sinh từ title, tự động thêm hậu tố số (-2, -3, ...) nếu trùng,
 * để không bao giờ vi phạm cột `slug` unique khi tạo 2 bài trùng tiêu đề.
 */
class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'cover_image_path',
        'meta_title',
        'meta_description',
        'author_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Article $article) {
            if (empty($article->slug)) {
                $article->slug = static::generateUniqueSlug($article->title);
            }
        });

        static::updating(function (Article $article) {
            // Chỉ tự sinh lại slug nếu title đổi VÀ người dùng không tự tay sửa slug
            // (tránh ghi đè slug cũ đã được index bởi Google nếu admin chỉ sửa nội dung).
            if ($article->isDirty('title') && !$article->isDirty('slug')) {
                $article->slug = static::generateUniqueSlug($article->title, $article->id);
            }
        });
    }

    /**
     * Sinh slug duy nhất từ title: "Tin abc" -> "tin-abc", nếu trùng
     * -> "tin-abc-2", "tin-abc-3", ...
     */
    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}