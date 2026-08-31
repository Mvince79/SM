<?php

namespace App\Settings;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * SettingService — trung tâm đọc/ghi các thông số hệ thống do admin chỉnh.
 *
 * Nguyên tắc an toàn ("rào chắn"):
 *  - Mọi giá trị đều có min/max/options từ SettingRegistry → admin KHÔNG thể nhập
 *    giá trị phi logic (setMany() luôn clamp + validate chéo trước khi lưu).
 *  - Nếu DB rỗng → trả về giá trị mặc định trong config (fallback an toàn, không
 *    bao giờ "trắng" hay nổ).
 *  - applyToConfig() ghi đè config tại runtime → mọi chỗ đọc config(...) cũ tự
 *    động nhận giá trị mới mà KHÔNG phải sửa từng đoạn code.
 */
final class SettingService
{
    /** Cache effective settings để tránh query DB mỗi request. */
    private const CACHE_KEY = 'system_settings_effective';

    private const CACHE_TTL = 300;

    /**
     * Toàn bộ giá trị hiệu lực, keyed bởi field.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn (): array => $this->loadEffective());
    }

    /**
     * Giá trị hiệu lực của 1 field (DB override hoặc default).
     *
     * @return mixed
     */
    public function get(string $field): mixed
    {
        return $this->all()[$field] ?? SettingRegistry::get($field)['default'] ?? null;
    }

    /**
     * Ghi nhiều thông số cùng lúc (từ form admin).
     *
     * - Bỏ qua field không có trong registry (tránh injection key lạ).
     * - Normalize + clamp từng giá trị.
     * - Hợp nhất với giá trị hiện tại rồi validate chéo (đảm bảo nhất quán ngay
     *   cả khi chỉ đổi 1 field).
     * - Lưu DB, ghi đè config, ghi audit log.
     *
     * @param array<string, mixed> $flat
     *
     * @throws ValidationException khi vi phạm ràng buộc (bao gồm ràng buộc chéo).
     */
    public function setMany(array $flat): void
    {
        $normalized = [];

        foreach ($flat as $field => $raw) {
            $def = SettingRegistry::get((string) $field);
            if ($def === null) {
                continue;
            }
            $normalized[$field] = $this->normalize($field, $raw);
        }

        // Hợp nhất với hiện tại để validate chéo trên tập đầy đủ.
        $merged = $this->all();
        foreach ($normalized as $field => $value) {
            $merged[$field] = $value;
        }

        $this->validateCrossField($merged);

        $oldSnapshot = $this->all();

        foreach ($normalized as $field => $value) {
            $def = SettingRegistry::get($field);
            SystemSetting::updateOrCreate(
                ['group' => $def['group'], 'key' => $def['key']],
                ['value' => json_encode($value), 'type' => $def['type']],
            );
        }

        $this->forgetCache();
        $this->applyToConfig();
        $this->audit($oldSnapshot, $this->all());
    }

    /**
     * Xoá toàn bộ override → quay về giá trị mặc định trong config.
     */
    public function resetAll(): void
    {
        $oldSnapshot = $this->all();

        SystemSetting::query()->delete();

        $this->forgetCache();
        $this->applyToConfig();
        $this->audit($oldSnapshot, $this->all());
    }

    /**
     * Ghi đè config() tại runtime từ DB (hoặc default). Gọi ở boot provider và
     * sau mỗi lần lưu, để mọi chỗ đọc config(...) cũ nhận giá trị mới.
     *
     * Có guard Schema::hasTable để không nổ khi chạy lệnh trước khi migrate.
     */
    public function applyToConfig(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        // all() đã hợp nhất DB override lên default → dùng trực tiếp cho đúng.
        foreach ($this->all() as $field => $value) {
            $def = SettingRegistry::get($field);
            Config::set("{$def['group']}.{$def['key']}", $value);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadEffective(): array
    {
        $rows = $this->tableExists()
            ? SystemSetting::query()->get()
            : collect();

        $map = [];
        foreach ($rows as $row) {
            $map["{$row->group}.{$row->key}"] = $row;
        }

        $result = [];
        foreach (SettingRegistry::all() as $field => $def) {
            $result[$field] = $this->effectiveValue($def, $map["{$def['group']}.{$def['key']}"] ?? null);
        }

        return $result;
    }

    /**
     * @param \App\Models\SystemSetting|null $row
     *
     * @return mixed
     */
    private function effectiveValue(array $def, ?SystemSetting $row = null)
    {
        if ($row === null) {
            return $def['default'];
        }

        $raw = $row->decodedValue();

        if ($raw === null) {
            return $def['default'];
        }

        return match ($def['type']) {
            'int' => (int) $raw,
            'float' => (float) $raw,
            'milestones' => is_array($raw) ? array_map('intval', $raw) : $def['default'],
            default => (string) $raw,
        };
    }

    /**
     * Chuẩn hóa + clamp 1 giá trị theo định nghĩa.
     *
     * @return mixed
     */
    private function normalize(string $field, mixed $raw): mixed
    {
        $def = SettingRegistry::get($field);
        $type = $def['type'];

        if ($type === 'int') {
            $v = (int) $raw;
            $v = $this->clamp($v, $def['min'] ?? null, $def['max'] ?? null);

            return $v;
        }

        if ($type === 'float') {
            $v = (float) $raw;
            $v = $this->clamp($v, $def['min'] ?? null, $def['max'] ?? null);
            if (! empty($def['step'])) {
                $v = round($v / $def['step']) * $def['step'];
            }
            $v = round($v, 4);

            return $v;
        }

        if ($type === 'select') {
            return in_array($raw, array_keys($def['options'] ?? []), true)
                ? $raw
                : $def['default'];
        }

        if ($type === 'milestones') {
            if (! is_array($raw)) {
                return $def['default'];
            }

            $values = collect($raw)
                ->map(fn ($item): int => is_array($item) ? (int) ($item['value'] ?? 0) : (int) $item)
                ->filter(fn (int $v): bool => $v >= 1)
                ->unique()
                ->sort()
                ->values()
                ->take(5)
                ->all();

            return $values;
        }

        return (string) $raw;
    }

    private function clamp(float|int $value, mixed $min, mixed $max): float|int
    {
        if ($min !== null && $value < $min) {
            return $min;
        }
        if ($max !== null && $value > $max) {
            return $max;
        }

        return $value;
    }

    /**
     * Ràng buộc chéo đảm bảo tập thông số NHẤT QUÁN và LOGIC.
     *
     * @param array<string, mixed> $values
     *
     * @throws ValidationException
     */
    private function validateCrossField(array $values): void
    {
        $errors = [];

        $esc = $values['escalate_after_days'] ?? null;
        $close = $values['close_after_days'] ?? null;
        if ($esc !== null && $close !== null && ! ($close > $esc)) {
            $errors['close_after_days'] = 'Mức "Đóng hồ sơ sau" phải lớn hơn mức "Leo thang sau".';
        }

        $search = $values['search_threshold'] ?? null;
        $score = $values['score_threshold'] ?? null;
        $high = $values['high_confidence_threshold'] ?? null;

        if ($search !== null && $score !== null && $search > $score) {
            $errors['search_threshold'] = 'Ngưỡng hiển thị tìm kiếm phải nhỏ hơn hoặc bằng ngưỡng khớp.';
        }
        if ($score !== null && $high !== null && $high <= $score) {
            $errors['high_confidence_threshold'] = 'Ngưỡng tin cậy cao phải lớn hơn ngưỡng khớp.';
        }

        $milestones = $values['reminder_milestones'] ?? [];
        if (is_array($milestones) && $close !== null) {
            foreach ($milestones as $m) {
                if ((int) $m > (int) $close) {
                    $errors['reminder_milestones'] = 'Mọi mốc nhắc phải nhỏ hơn mức "Đóng hồ sơ sau".';

                    break;
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     */
    private function audit(array $old, array $new): void
    {
        try {
            $user = Auth::user();
            $request = request();

            AuditLog::create([
                'actor_id' => $user?->getAuthIdentifier(),
                'actor_role' => $user?->role ?? 'ADMIN',
                'action' => 'system_settings.updated',
                'target_type' => 'system_settings',
                'target_id' => 'global',
                'old_value' => $old,
                'new_value' => $new,
                'reason' => null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Ghi audit thất bại KHÔNG được làm hỏng việc lưu cài đặt.
            report($e);
        }
    }

    private function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('system_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
