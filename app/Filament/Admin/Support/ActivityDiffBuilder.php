<?php

namespace App\Filament\Admin\Support;

use Illuminate\Support\HtmlString;
use App\Filament\Admin\Support\Labels;
use App\Models\User;

/**
 * D-03 — Build nội dung modal/slide-over hiển thị chi tiết một dòng activity_log.
 * Luôn hiển thị tóm tắt (sự kiện, thời gian, người thực hiện, đối tượng, mô tả)
 * dù có hay không có diff trường — tránh modal trắng.
 */
class ActivityDiffBuilder
{
    /**
     * Các cột không hiển thị trong diff (dù có thay đổi).
     */
    private static array $skipAttributes = [
        'updated_at',
        'created_at',
        'view_count',
        'share_count',
    ];

    public static function build($record): HtmlString
    {
        $properties = $record->properties ?? collect();
        $old = is_array($o = $properties->get('old', [])) ? $o : (array) $o;
        $new = is_array($a = $properties->get('attributes', [])) ? $a : (array) $a;

        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        sort($keys);

        $keys = array_values(array_filter($keys, function (string $key) use ($old, $new): bool {
            if (in_array($key, self::$skipAttributes, true)) {
                return false;
            }
            $oldNorm = self::normalizeForCompare($old[$key] ?? null);
            $newNorm = self::normalizeForCompare($new[$key] ?? null);

            return $oldNorm !== $newNorm;
        }));

        $hasChanges = ! empty($keys);

        $causer = $record->causer;
        $causerName = $causer?->name ?? $causer?->full_name ?? 'Hệ thống';
        $initials = collect(explode(' ', $causerName))
            ->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))
            ->take(2)
            ->join('');
        $subjectName = $record->subject?->full_name ?? $record->subject?->title ?? '';
        $subjectLink = RecordLinkResolver::urlByModel($record->subject_type, $record->subject_id);
        $subjectLabel = RecordLinkResolver::label(null, $record->subject_type)
            . ' #' . ($record->subject_id ?? '');
        $time = $record->created_at?->format('d/m/Y H:i:s') ?? '';
        $description = $record->description ?: '';

        $eventLabel = match ($record->event) {
            'created' => 'Tạo mới',
            'updated' => 'Cập nhật',
            'deleted' => 'Xóa',
            default => ucfirst((string) $record->event),
        };
        $eventIcon = match ($record->event) {
            'created' => 'ti-plus',
            'updated' => 'ti-pencil',
            'deleted' => 'ti-trash',
            default => 'ti-activity',
        };
        $eventBg = match ($record->event) {
            'created' => 'var(--color-background-success)',
            'updated' => 'var(--color-background-warning)',
            'deleted' => 'var(--color-background-danger)',
            default => 'var(--color-background-secondary)',
        };
        $eventColor = match ($record->event) {
            'created' => 'var(--color-text-success)',
            'updated' => 'var(--color-text-warning)',
            'deleted' => 'var(--color-text-danger)',
            default => 'var(--color-text-secondary)',
        };

        // Phần diff (chỉ khi có thay đổi trường).
        if ($hasChanges) {
            $rows = '';
            foreach ($keys as $i => $key) {
                $oldVal = array_key_exists($key, $old)
                    ? self::formatValue(self::resolveValue($key, $old[$key]))
                    : null;
                $newVal = array_key_exists($key, $new)
                    ? self::formatValue(self::resolveValue($key, $new[$key]))
                    : null;
                $rowBg = $i % 2 === 1 ? 'background:var(--color-background-secondary);' : '';

                $oldCell = $oldVal !== null
                    ? "<span style='display:inline-block;background:#FCEBEB;color:#A32D2D;"
                    . "font-size:12px;font-family:monospace;padding:3px 8px;border-radius:4px;"
                    . "border:0.5px solid #F7C1C1;word-break:break-all;'>{$oldVal}</span>"
                    : "<span style='font-size:12px;color:var(--color-text-tertiary);font-style:italic;'>—</span>";

                $newCell = $newVal !== null
                    ? "<span style='display:inline-block;background:#EAF3DE;color:#3B6D11;"
                    . "font-size:12px;font-family:monospace;padding:3px 8px;border-radius:4px;"
                    . "border:0.5px solid #C0DD97;word-break:break-all;'>{$newVal}</span>"
                    : "<span style='font-size:12px;color:var(--color-text-tertiary);font-style:italic;'>—</span>";

                $rows .= "
                <div style='display:grid;grid-template-columns:150px 1fr 1fr;gap:0;"
                    . "padding:0.55rem 1.25rem;align-items:start;{$rowBg}'>
                    <span style='font-size:12px;font-family:monospace;"
                    . "color:var(--color-text-secondary);padding-top:4px;'>"
                    . htmlspecialchars(Labels::field($key)) . "</span>
                    <div style='padding-right:8px;'>{$oldCell}</div>
                    <div>{$newCell}</div>
                </div>";
            }

            $diffSection = "
            <div style='padding:0.6rem 1.25rem;border-bottom:0.5px solid var(--color-border-tertiary);"
            . "display:grid;grid-template-columns:150px 1fr 1fr;font-size:11px;font-weight:500;"
            . "color:var(--color-text-tertiary);text-transform:uppercase;letter-spacing:0.05em;'>
                <span>Trường</span>
                <span style='display:flex;align-items:center;gap:4px;'>
                    <span style='width:7px;height:7px;border-radius:50%;background:#E24B4A;display:inline-block;'></span>Trước
                </span>
                <span style='display:flex;align-items:center;gap:4px;'>
                    <span style='width:7px;height:7px;border-radius:50%;background:#639922;display:inline-block;'></span>Sau
                </span>
            </div>
            <div style='padding:0.4rem 0;'>{$rows}</div>";
        } else {
            $diffSection = "
            <div style='padding:1.25rem;color:var(--color-text-secondary);font-size:13px;'>
                Không ghi nhận thay đổi trường cụ thể cho sự kiện này.
            </div>";
        }

        $subjectHtml = $subjectLink
            ? "<a href=\"{$subjectLink}\" target=\"_blank\" rel=\"noopener\""
            . " style='color:var(--color-text-info);text-decoration:underline;font-weight:500;'>{$subjectLabel}</a>"
            : "<span style='font-weight:500;color:var(--color-text-primary);'>{$subjectLabel}</span>";

        $descriptionHtml = $description
            ? "<div style='padding:0.5rem 1.25rem 0.75rem;border-bottom:0.5px solid var(--color-border-tertiary);"
            . "font-size:13px;color:var(--color-text-secondary);'>{$description}</div>"
            : '';

        return new HtmlString("
        <div style='font-family:inherit;'>

            <div style='padding:1rem 1.25rem;border-bottom:0.5px solid var(--color-border-tertiary);"
            . "display:flex;align-items:center;justify-content:space-between;'>
                <div style='display:flex;align-items:center;gap:8px;'>
                    <span style='background:{$eventBg};color:{$eventColor};font-size:12px;"
            . "font-weight:500;padding:3px 10px;border-radius:6px;'>
                        <i class=\"ti {$eventIcon}\" style='font-size:12px;vertical-align:-1px;margin-right:3px;'></i>{$eventLabel}
                    </span>
                </div>
                <span style='font-size:12px;color:var(--color-text-tertiary);'>
                    <i class=\"ti ti-clock\" style='font-size:13px;vertical-align:-1px;margin-right:3px;'></i>{$time}
                </span>
            </div>

            <div style='padding:0.75rem 1.25rem;border-bottom:0.5px solid var(--color-border-tertiary);"
            . "display:flex;align-items:center;gap:8px;'>
                <div style='width:26px;height:26px;border-radius:50%;background:var(--color-background-info);"
            . "display:flex;align-items:center;justify-content:center;font-size:10px;"
            . "font-weight:500;color:var(--color-text-info);flex-shrink:0;'>{$initials}</div>
                <span style='font-size:13px;color:var(--color-text-secondary);'>
                    <strong style='font-weight:500;color:var(--color-text-primary);'>{$causerName}</strong>
                    đã thực hiện trên
                    {$subjectHtml}
                </span>
            </div>

            {$descriptionHtml}

            {$diffSection}

        </div>");
    }

    private static function normalizeForCompare(mixed $value): string
    {
        if (is_null($value)) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_numeric($value)) {
            return (string) (float) $value;
        }

        return trim((string) $value);
    }

    /**
     * Dịch giá trị hiển thị trong diff theo loại trường:
     *  - status        → Labels::status (đã có)
     *  - category/priority → Labels::attributeValue (enum tiếng Việt)
     *  - user_id       → tên người dùng (lookup 1 bản ghi, chỉ chạy khi mở modal)
     *  - còn lại       → giữ nguyên (bao gồm subject là nội dung tự do của user)
     */
    private static function resolveValue(string $key, mixed $raw): mixed
    {
        if ($key === 'status') {
            return Labels::status((string) $raw);
        }

        if ($key === 'user_id' && is_numeric($raw)) {
            return User::find((int) $raw)?->full_name ?? $raw;
        }

        if (in_array($key, ['category', 'priority'], true)) {
            return Labels::attributeValue($key, (string) $raw);
        }

        return $raw;
    }

    private static function formatValue(mixed $value): string
    {
        if (is_null($value)) {
            return '—';
        }

        if (is_bool($value)) {
            return Labels::bool($value);
        }

        if (is_array($value) || is_object($value)) {
            return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        if (is_numeric($value) && str_contains((string) $value, '.')) {
            return htmlspecialchars(rtrim(rtrim((string) $value, '0'), '.'));
        }

        return htmlspecialchars((string) $value);
    }
}
