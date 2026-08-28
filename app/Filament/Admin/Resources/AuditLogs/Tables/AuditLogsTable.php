<?php

namespace App\Filament\Admin\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // D-02 — Hiển thị dạng timeline: [thời gian] — [actor] đã [action] hồ sơ [target] — Lý do.
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
                    ->searchable(),

                TextColumn::make('target')
                    ->label('Hồ sơ')
                    ->getStateUsing(fn (AuditLog $r) => "{$r->target_type} #{$r->target_id}"),

                TextColumn::make('change')
                    ->label('Thay đổi trạng thái')
                    ->getStateUsing(fn (AuditLog $r) => data_get($r->old_value, 'status', '—') . ' → ' . data_get($r->new_value, 'status', '—')),

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
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('to')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
