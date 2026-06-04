<?php

namespace App\Filament\Admin\Resources\MissingChildren\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ActivityLogRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity Log';

    protected static ?string $recordTitleAttribute = 'description';

    /**
     * Các cột không hiển thị trong diff (dù có thay đổi).
     */
    private static array $skipAttributes = [
        'updated_at',
        'created_at',
        'view_count',
        'share_count',
    ];

    /**
     * Không có form vì đây là readonly – chỉ xem, không tạo/sửa.
     */
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query->latest()
            )
            ->columns([
                TextColumn::make('event')
                    ->label('Sự kiện')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'created' => 'Tạo mới',
                        'updated' => 'Cập nhật',
                        'deleted' => 'Xóa',
                        default => ucfirst((string) $state),
                    })
                    ->color(fn(?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Mô tả')
                    ->limit(60)
                    ->tooltip(fn($record) => $record->description)
                    ->searchable(),

                TextColumn::make('causer.name')
                    ->label('Thực hiện bởi')
                    ->default('Hệ thống')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Loại sự kiện')
                    ->options([
                        'created' => 'Tạo mới',
                        'updated' => 'Cập nhật',
                        'deleted' => 'Xóa',
                    ]),

                Filter::make('created_at')
                    ->label('Khoảng thời gian')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Từ ngày')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Đến ngày')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $q, $date) => $q->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $q, $date) => $q->whereDate('created_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Từ: ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Đến: ' . $data['until'];
                        }
                        return $indicators;
                    }),
            ])
            ->toolbarActions([])
            ->recordActions([
                Action::make('view_diff')
                    ->label('Xem thay đổi')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Chi tiết thay đổi – ' . ucfirst((string) $record->event))
                    ->modalContent(fn($record) => self::buildDiffModal($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    /**
     * Normalize giá trị để so sánh:
     * – Số thực bỏ trailing zero: "28.00" → "28"
     * – Null/empty về chuỗi rỗng
     * – String trim whitespace
     */
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
     * Build nội dung modal hiển thị diff:
     *  – properties.old        → nền đỏ (trước)
     *  – properties.attributes → nền xanh (sau)
     * Lọc bỏ các cột trong $skipAttributes và các field
     * mà giá trị thực sự không đổi sau khi normalize (vd: 28.00 vs 28).
     */
    protected static function buildDiffModal($record): HtmlString
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

        $causer = $record->causer;
        $causerName = $causer?->name ?? $causer?->full_name ?? 'Hệ thống';
        $initials = collect(explode(' ', $causerName))
            ->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))
            ->take(2)
            ->join('');
        $subjectName = $record->subject?->full_name ?? '';
        $time = $record->created_at?->format('d/m/Y H:i:s') ?? '';

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

        if (empty($keys)) {
            return new HtmlString(
                '<p style="font-size:13px;color:var(--color-text-secondary);padding:1.5rem;">'
                . 'Không có dữ liệu thay đổi.</p>'
            );
        }

        $rows = '';
        foreach ($keys as $i => $key) {
            $oldVal = array_key_exists($key, $old) ? self::formatValue($old[$key]) : null;
            $newVal = array_key_exists($key, $new) ? self::formatValue($new[$key]) : null;
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
                . htmlspecialchars($key) . "</span>
                <div style='padding-right:8px;'>{$oldCell}</div>
                <div>{$newCell}</div>
            </div>";
        }

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
                    đã chỉnh sửa hồ sơ
                    <strong style='font-weight:500;color:var(--color-text-primary);'>{$subjectName}</strong>
                </span>
            </div>

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

            <div style='padding:0.4rem 0;'>
                {$rows}
            </div>

        </div>");
    }

    /**
     * Format một giá trị bất kỳ thành chuỗi hiển thị trong modal.
     */
    protected static function formatValue(mixed $value): string
    {
        if (is_null($value)) {
            return '<em style="color:var(--color-text-tertiary);">null</em>';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
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