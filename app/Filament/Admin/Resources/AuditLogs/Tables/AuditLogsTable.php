<?php

namespace App\Filament\Admin\Resources\AuditLogs\Tables;

use App\Filament\Admin\Support\RecordLinkResolver;
use App\Filament\Admin\Support\Labels;
use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('actor')
                    ->label('Người thực hiện')
                    ->getStateUsing(fn (AuditLog $r) => match ($r->actor_role) {
                        'ADMIN' => 'Quản trị viên',
                        'SYSTEM' => 'Hệ thống',
                        'FAMILY' => 'Gia đình',
                        default => $r->actor_role ?? '—',
                    })
                    ->badge()
                    ->color(fn (AuditLog $r) => match ($r->actor_role) {
                        'ADMIN' => 'danger',
                        'SYSTEM' => 'gray',
                        'FAMILY' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('action')
                    ->label('Hành động')
                    ->getStateUsing(
                        fn (AuditLog $r) => 'Đổi trạng thái ' . RecordLinkResolver::label($r->target_type)
                    )
                    ->searchable()
                    ->badge()
                    ->color(fn (AuditLog $r) => match (data_get($r->new_value, 'status')) {
                        'REUNITED' => 'success',
                        'UNREACHABLE_FAMILY' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('target')
                    ->label('Hồ sơ')
                    ->getStateUsing(
                        fn (AuditLog $r) => RecordLinkResolver::label($r->target_type) . ' #' . $r->target_id
                    )
                    ->url(fn (AuditLog $r) => RecordLinkResolver::url($r->target_type, $r->target_id))
                    ->openUrlInNewTab(),

                TextColumn::make('change')
                    ->label('Thay đổi trạng thái')
                    ->getStateUsing(
                        fn (AuditLog $r) => Labels::status(data_get($r->old_value, 'status'))
                            . ' → ' . Labels::status(data_get($r->new_value, 'status'))
                    ),

                TextColumn::make('reason')
                    ->label('Lý do')
                    ->limit(40)
                    ->tooltip(fn (AuditLog $r) => $r->reason),
            ])
            ->filters([
                SelectFilter::make('target_type')
                    ->label('Loại hồ sơ')
                    ->options(
                        fn () => AuditLog::query()->distinct()->pluck('target_type', 'target_type')->toArray()
                    ),

                SelectFilter::make('actor_role')
                    ->label('Vai trò')
                    ->options([
                        'ADMIN' => 'Quản trị viên',
                        'SYSTEM' => 'Hệ thống',
                        'FAMILY' => 'Gia đình',
                    ]),

                Filter::make('created_at')
                    ->label('Khoảng thời gian')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày')->native(false),
                        DatePicker::make('to')->label('Đến ngày')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                Action::make('view_detail')
                    ->label('Xem chi tiết')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Chi tiết nhật ký kiểm toán')
                    ->modalContent(fn (AuditLog $r) => self::auditDetailHtml($r))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    /**
     * Build nội dung modal "Xem chi tiết" nhật ký kiểm toán (HTML, không dùng
     * infolist để tránh lỗi render trên table action).
     */
    private static function auditDetailHtml(AuditLog $r): HtmlString
    {
        $time = $r->created_at?->format('d/m/Y H:i:s') ?? '';
        $actorLabel = match ($r->actor_role) {
            'ADMIN' => 'Quản trị viên',
            'SYSTEM' => 'Hệ thống',
            'FAMILY' => 'Gia đình',
            default => $r->actor_role ?? '—',
        };
        $actorColor = match ($r->actor_role) {
            'ADMIN' => 'var(--color-background-danger)',
            'SYSTEM' => 'var(--color-background-secondary)',
            'FAMILY' => 'var(--color-background-success)',
            default => 'var(--color-background-secondary)',
        };
        $actorTextColor = match ($r->actor_role) {
            'ADMIN' => 'var(--color-text-danger)',
            'SYSTEM' => 'var(--color-text-secondary)',
            'FAMILY' => 'var(--color-text-success)',
            default => 'var(--color-text-secondary)',
        };

        $targetLabel = RecordLinkResolver::label($r->target_type) . ' #' . $r->target_id;
        $targetLink = RecordLinkResolver::url($r->target_type, $r->target_id);
        $targetHtml = $targetLink
            ? "<a href=\"{$targetLink}\" target=\"_blank\" rel=\"noopener\""
            . " style='color:var(--color-text-info);text-decoration:underline;font-weight:500;'>{$targetLabel}</a>"
            : "<span style='font-weight:500;color:var(--color-text-primary);'>{$targetLabel}</span>";

        $oldStatus = Labels::status(data_get($r->old_value, 'status'));
        $newStatus = Labels::status(data_get($r->new_value, 'status'));
        $reason = $r->reason ?: '—';
        $ip = $r->ip_address ?: '—';
        $uaShort = Labels::userAgent($r->user_agent);
        $uaFull = $r->user_agent ? htmlspecialchars((string) $r->user_agent, ENT_QUOTES) : '—';

        return new HtmlString("
        <div style='font-family:inherit;'>

            <div style='padding:1rem 1.25rem;border-bottom:0.5px solid var(--color-border-tertiary);"
            . "display:flex;align-items:center;justify-content:space-between;'>
                <span style='background:var(--color-background-warning);color:var(--color-text-warning);"
            . "font-size:12px;font-weight:500;padding:3px 10px;border-radius:6px;'>
                    <i class=\"ti ti-exchange\" style='font-size:12px;vertical-align:-1px;margin-right:3px;'></i>
                    Đổi trạng thái {$targetLabel}
                </span>
                <span style='font-size:12px;color:var(--color-text-tertiary);'>
                    <i class=\"ti ti-clock\" style='font-size:13px;vertical-align:-1px;margin-right:3px;'></i>{$time}
                </span>
            </div>

            <div style='padding:0.75rem 1.25rem;border-bottom:0.5px solid var(--color-border-tertiary);"
            . "display:flex;align-items:center;gap:8px;'>
                <span style='background:{$actorColor};color:{$actorTextColor};font-size:12px;"
            . "font-weight:500;padding:3px 10px;border-radius:6px;'>{$actorLabel}</span>
                <span style='font-size:13px;color:var(--color-text-secondary);'>
                    đã thực hiện trên {$targetHtml}
                </span>
            </div>

            <div style='padding:0.75rem 1.25rem;border-bottom:0.5px solid var(--color-border-tertiary);'>
                <div style='font-size:11px;font-weight:500;color:var(--color-text-tertiary);"
                . "text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;'>Thay đổi trạng thái</div>
                <div style='display:flex;align-items:center;gap:10px;'>
                    <span style='display:inline-block;background:#FCEBEB;color:#A32D2D;font-size:13px;"
                    . "padding:4px 12px;border-radius:6px;border:0.5px solid #F7C1C1;'>{$oldStatus}</span>
                    <i class=\"ti ti-arrow-right\" style='color:var(--color-text-tertiary);'></i>
                    <span style='display:inline-block;background:#EAF3DE;color:#3B6D11;font-size:13px;"
                    . "padding:4px 12px;border-radius:6px;border:0.5px solid #C0DD97;'>{$newStatus}</span>
                </div>
            </div>

            <div style='padding:0.75rem 1.25rem;border-bottom:0.5px solid var(--color-border-tertiary);'>
                <div style='font-size:11px;font-weight:500;color:var(--color-text-tertiary);"
                . "text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;'>Lý do</div>
                <div style='font-size:13px;color:var(--color-text-secondary);'>{$reason}</div>
            </div>

            <div style='padding:0.75rem 1.25rem;display:grid;grid-template-columns:1fr 1fr;gap:12px;'>
                <div>
                    <div style='font-size:11px;font-weight:500;color:var(--color-text-tertiary);"
                    . "text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;'>Địa chỉ IP</div>
                    <div style='font-size:13px;color:var(--color-text-secondary);word-break:break-all;'>{$ip}</div>
                </div>
                <div>
                    <div style='font-size:11px;font-weight:500;color:var(--color-text-tertiary);"
                    . "text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;'>User-Agent</div>
                    <div style='font-size:13px;color:var(--color-text-secondary);word-break:break-all;' title='{$uaFull}'>{$uaShort}</div>
                </div>
            </div>

        </div>");
    }
}
