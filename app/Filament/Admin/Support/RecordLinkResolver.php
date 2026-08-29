<?php

namespace App\Filament\Admin\Support;

use App\Filament\Admin\Resources\Articles\ArticleResource;
use App\Filament\Admin\Resources\Clues\ClueResource;
use App\Filament\Admin\Resources\FaceMatchResults\FaceMatchResultResource;
use App\Filament\Admin\Resources\FoundChildResource\FoundChildResource;
use App\Filament\Admin\Resources\KycVerifications\KycVerificationResource;
use App\Filament\Admin\Resources\MissingChildren\MissingChildResource;
use App\Filament\Admin\Resources\SupportTickets\SupportTicketResource;
use App\Filament\Admin\Resources\Users\UserResource;
use Throwable;

/**
 * D-02 — Giải quyết liên kết "xem nguồn" từ một dòng log (activity / audit)
 * về đúng trang chi tiết (view) của record gốc trong Filament admin.
 *
 * Không throw: nếu model/resource không tồn tại hoặc chưa có trang view,
 * trả về null để cột tương ứng không bấm được (an toàn).
 */
class RecordLinkResolver
{
    /** map table_name → Filament Resource class có trang view. */
    protected static array $tableToResource = [
        'missing_children' => MissingChildResource::class,
        'found_children' => FoundChildResource::class,
        'users' => UserResource::class,
        'articles' => ArticleResource::class,
        'clues' => ClueResource::class,
        'support_tickets' => SupportTicketResource::class,
        'face_match_results' => FaceMatchResultResource::class,
        'kyc_verifications' => KycVerificationResource::class,
    ];

    /** Nhãn hiển thị thân thiện (Tiếng Việt) cho mỗi loại record. */
    protected static array $tableLabels = [
        'missing_children' => 'Hồ sơ trẻ mất tích',
        'found_children' => 'Trẻ được tìm thấy',
        'users' => 'Người dùng',
        'articles' => 'Bài viết',
        'clues' => 'Manh mối',
        'support_tickets' => 'Yêu cầu hỗ trợ',
        'face_match_results' => 'Kết quả đối chiếu',
        'kyc_verifications' => 'Xác minh KYC',
    ];

    /**
     * URL xem chi tiết của record theo table_name + id.
     */
    public static function url(?string $table, $id): ?string
    {
        if (blank($table) || blank($id)) {
            return null;
        }

        $resource = self::$tableToResource[$table] ?? null;

        if ($resource === null) {
            return null;
        }

        try {
            /** @var \Filament\Resources\Resource $resource */
            return $resource::getUrl('view', ['record' => $id]);
        } catch (Throwable) {
            // Một số resource không có trang view → fallback sang edit.
            try {
                return $resource::getUrl('edit', ['record' => $id]);
            } catch (Throwable) {
                return null;
            }
        }
    }

    /**
     * URL xem chi tiết theo FQCN model (dùng cho activity_log có subject_type).
     */
    public static function urlByModel(?string $modelFqcn, $id): ?string
    {
        if (blank($modelFqcn) || blank($id)) {
            return null;
        }

        try {
            $table = app($modelFqcn)->getTable();
        } catch (Throwable) {
            return null;
        }

        return self::url($table, $id);
    }

    /**
     * Nhãn thân thiện cho table_name, fallback class_basename của model.
     * Nếu chỉ có modelFqcn, tự resolve table để lấy nhãn Tiếng Việt.
     */
    public static function label(?string $table, ?string $modelFqcn = null): string
    {
        if ($table && isset(self::$tableLabels[$table])) {
            return self::$tableLabels[$table];
        }

        if ($modelFqcn) {
            try {
                $resolved = app($modelFqcn)->getTable();
            } catch (Throwable) {
                $resolved = null;
            }

            if ($resolved && isset(self::$tableLabels[$resolved])) {
                return self::$tableLabels[$resolved];
            }

            return class_basename($modelFqcn);
        }

        return $table ?: '—';
    }
}
