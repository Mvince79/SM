<?php

namespace App\Filament\Admin\Resources\MissingChildren\Tables;

use App\Models\HandoverPoint;
use App\Models\MissingChild;
use App\Models\Province;
use App\Services\MissingChildCascadeService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Events\PostStatusChanged;
use Illuminate\Support\Facades\Mail;

class MissingChildrenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('primaryPhoto.path_thumb')
                    ->label('')
                    ->defaultImageUrl(
                        fn(MissingChild $record) =>
                        'https://ui-avatars.com/api/?name=' . urlencode($record->case_code)
                        . '&size=48&background=e2e8f0&color=64748b&bold=true'
                    )
                    ->circular()
                    ->imageSize(48)
                    ->disk('public'),

                TextColumn::make('case_code')
                    ->label('Mã hồ sơ')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('full_name')
                    ->label('Họ tên')
                    ->searchable()
                    ->sortable()
                    ->description(fn(MissingChild $record) => $record->province?->name),

                TextColumn::make('age_estimate')
                    ->label('Tuổi')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('gender')
                    ->label('Giới tính')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'MALE' => 'Nam',
                        'FEMALE' => 'Nữ',
                        default => 'Không rõ',
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'MALE' => 'info',
                        'FEMALE' => 'pink',
                        default => 'gray',
                    }),

                TextColumn::make('missing_date')
                    ->label('Ngày mất tích')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'APPROVED' => 'success',
                        'PENDING' => 'warning',
                        'REJECTED' => 'danger',
                        'FOUND' => 'primary',
                        'HANDED_OVER' => 'info',
                        'REUNITED' => 'success',
                        'UNREACHABLE_FAMILY' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'PENDING' => 'Chờ duyệt',
                        'APPROVED' => 'Đã duyệt',
                        'FOUND' => 'Đã tìm thấy',
                        'CLOSED' => 'Đóng',
                        'REJECTED' => 'Từ chối',
                        'HANDED_OVER' => 'Đã bàn giao',
                        'REUNITED' => 'Đã đoàn tụ',
                        'UNREACHABLE_FAMILY' => 'Không liên lạc được',
                        default => $state,
                    }),

                TextColumn::make('clues_count')
                    ->label('Manh mối')
                    ->counts('clues')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('comments_count')
                    ->label('Bình luận')
                    ->counts('comments')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label('Ngày duyệt')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'PENDING' => 'Chờ duyệt',
                        'APPROVED' => 'Đã duyệt',
                        'FOUND' => 'Đã tìm thấy',
                        'CLOSED' => 'Đóng',
                        'REJECTED' => 'Từ chối',
                    ])
                    ->native(false)
                    ->columnSpan(1),

                SelectFilter::make('gender')
                    ->label('Giới tính')
                    ->options([
                        'MALE' => 'Nam',
                        'FEMALE' => 'Nữ',
                        'UNKNOWN' => 'Không rõ',
                    ])
                    ->native(false)
                    ->columnSpan(1),

                SelectFilter::make('skin_tone')
                    ->label('Màu da')
                    ->options([
                        'LIGHT' => 'Sáng',
                        'MEDIUM' => 'Trung bình',
                        'DARK' => 'Tối',
                    ])
                    ->native(false)
                    ->columnSpan(1),

                SelectFilter::make('province_id')
                    ->label('Tỉnh / Thành')
                    ->options(fn() => Province::orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->native(false)
                    ->columnSpan(1),

                Filter::make('missing_date')
                    ->label('Ngày mất tích')
                    ->columnSpan('full')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                    ])
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Từ ngày')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Đến ngày')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('missing_date', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('missing_date', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data) {
                        $parts = [];
                        if ($data['from'])
                            $parts[] = 'Từ ' . $data['from'];
                        if ($data['until'])
                            $parts[] = 'Đến ' . $data['until'];
                        return implode(' — ', $parts) ?: null;
                    }),

                Filter::make('age_estimate')
                    ->label('Tuổi ước tính')
                    ->columnSpan('full')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                    ])
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('age_min')
                            ->label('Tuổi từ')
                            ->numeric()
                            ->minValue(0),
                        \Filament\Forms\Components\TextInput::make('age_max')
                            ->label('Tuổi đến')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['age_min'], fn($q) => $q->where('age_estimate', '>=', (int) $data['age_min']))
                            ->when($data['age_max'], fn($q) => $q->where('age_estimate', '<=', (int) $data['age_max']));
                    })
                    ->indicateUsing(function (array $data) {
                        $parts = [];
                        if ($data['age_min'])
                            $parts[] = 'Tuổi ≥ ' . $data['age_min'];
                        if ($data['age_max'])
                            $parts[] = 'Tuổi ≤ ' . $data['age_max'];
                        return implode(', ', $parts) ?: null;
                    }),

                TrashedFilter::make()
                    ->label('Đã xoá')
                    ->native(false)
                    ->columnSpan('full'),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 2,
                'md' => 2,
                'lg' => 2,
                'xl' => 2,
            ])
            ->filtersTriggerAction(
                fn (Action $action): Action => $action
                    ->button()
                    ->label('Bộ lọc')
                    ->icon('heroicon-o-funnel')
            )
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->action(function (MissingChild $record) {
                        // Khi xoá vĩnh việt hồ sơ đã nằm trong thùng rác, kết quả đối chiếu
                        // CONFIRMED/PENDING thường đã bị cascade bác bỏ lúc soft-delete — chạy
                        // lại ở đây là no-op an toàn, nhưng vẫn giữ để không sót match "treo"
                        // trong trường hợp hồ sơ bị force-delete từ trạng thái khác.
                        DB::transaction(function () use ($record) {
                            app(MissingChildCascadeService::class)->handleRemoval($record, includePending: true);
                            $record->forceDelete();
                        });
                    }),

                Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Duyệt hồ sơ?')
                    ->modalDescription(fn(MissingChild $record) => "Duyệt hồ sơ \"{$record->full_name}\" ({$record->case_code})?")
                    ->visible(fn(MissingChild $record) => $record->status === 'PENDING')
                    ->action(function (MissingChild $record) {
                        // Chuông + email cho gia đình do MissingChildObserver phát
                        // (transition → APPROVED). Trước đây email gửi riêng ở đây
                        // nên nút Duyệt ở trang chi tiết lại không gửi gì.
                        // status/approved_at/approved_by bị guard → phải forceFill mới đổi được
                        // (update() gọi fill() sẽ bỏ qua silently, nút báo thành công nhưng status đứng yên).
                        $record->forceFill([
                            'status' => MissingChild::STATUS_APPROVED,
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                        ])->save();
                        Cache::forget('badge_missing_pending');
                        Notification::make()->title('Đã duyệt hồ sơ')->success()->send();
                        PostStatusChanged::dispatch($record->refresh());
                    }),

                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(MissingChild $record) => $record->status === 'PENDING')
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (MissingChild $record, array $data) {
                        // Cascade revert (quyết định #1) chung 1 transaction: trả FoundChild
                        // liên quan về trạng thái trước và bác bỏ kết quả đối chiếu CONFIRMED,
                        // tránh "Đã tìm thấy" giả. Không đụng thông báo Từ chối (riêng).
                        DB::transaction(function () use ($record, $data) {
                            $record->forceFill([
                                'status' => MissingChild::STATUS_REJECTED,
                                'rejection_reason' => $data['rejection_reason'],
                            ])->save();

                            app(MissingChildCascadeService::class)->handleRemoval($record, includePending: false);
                        });
                        Cache::forget('badge_missing_pending');
                        Notification::make()->title('Đã từ chối hồ sơ')->danger()->send();
                        PostStatusChanged::dispatch($record->refresh());
                    }),

                // H-01/H-04 — Đánh dấu "đã bàn giao" (mở khóa toàn bộ Nhóm H).
                // Hệ thống chỉ ghi nhận, không thay thế cơ quan chức năng.
                Action::make('mark_handed_over')
                    ->label('Đánh dấu đã bàn giao')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('info')
                    ->visible(fn(MissingChild $record) => in_array($record->status, ['APPROVED', 'FOUND'], true))
                    ->form([
                        Select::make('handover_point_id')
                            ->label('Điểm tiếp nhận / bàn giao')
                            ->options(fn() => HandoverPoint::active()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->nullable()
                            ->helperText('Nơi trẻ đang được giữ (nếu biết). Gia đình sẽ thấy địa chỉ & hotline này.'),
                        Textarea::make('reason')
                            ->label('Lý do xác nhận bàn giao (bắt buộc, tối thiểu 20 ký tự)')
                            ->required()
                            ->minLength(20)
                            ->rows(3)
                            ->helperText('Ví dụ: "Đã gọi điện xác minh qua gia đình, công an phường X xác nhận đã bàn giao bé vào ngày...".'),
                    ])
                    ->modalHeading('Đánh dấu đã bàn giao?')
                    ->modalDescription('Chuyển sang trạng thái "Đã bàn giao". Gia đình sẽ nhận hướng dẫn nhận lại con. Hệ thống chỉ ghi nhận thông tin, không thay thế cơ quan chức năng.')
                    ->modalSubmitActionLabel('Đánh dấu bàn giao')
                    ->action(function (MissingChild $record, array $data) {
                        // Dùng confirmHandoverByAdmin: validate lý do >= 20 ký tự TẠI ĐÂY
                        // (trước save) thay vì update() thô — StatusChangeAuditObserver chỉ
                        // chạy ở event `updated` (sau ghi DB) nên không rollback được.
                        $record->confirmHandoverByAdmin(
                            $data['reason'],
                            Auth::id(),
                            $data['handover_point_id'] ?? null
                        );
                        Cache::forget('dashboard_stats_overview');
                        Notification::make()->title('Đã đánh dấu bàn giao')->success()->send();
                    }),

                // H-04 — Admin xác nhận đoàn tụ hộ (đã liên hệ gia đình).
                // Bắt buộc nhập lý do (min 20 ký tự); observer ghi audit_logs
                // với actor_role = ADMIN. status → REUNITED.
                Action::make('confirm_reunion_admin')
                    ->label('Ghi nhận xác nhận của gia đình')
                    ->icon('heroicon-o-user-group')
                    ->color('success')
                    ->visible(fn(MissingChild $record) => $record->status === MissingChild::STATUS_HANDED_OVER)
                    ->schema([
                        Textarea::make('reason')
                            ->label('Lý do xác nhận (bắt buộc, tối thiểu 20 ký tự)')
                            ->required()
                            ->minLength(20)
                            ->rows(3)
                            ->helperText('Ghi rõ đã liên hệ gia đình qua kênh nào, kết quả xác minh ra sao.'),
                    ])
                    ->action(function (MissingChild $record, array $data) {
                        // Logic dùng chung với trang ConfirmReunionManually.
                        // Observer StatusChangeAuditObserver kiểm tra reason >= 20
                        // với actor_role = ADMIN và ghi audit_logs tương ứng.
                        $record->confirmReunionByAdmin($data['reason'], Auth::id());

                        Notification::make()
                            ->title('Đã xác nhận đoàn tụ (admin)')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('Duyệt hàng loạt')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Duyệt các hồ sơ đã chọn?')
                        ->modalDescription('Chỉ các hồ sơ đang ở trạng thái "Chờ duyệt" mới được xử lý.')
                        ->action(function (Collection $records) {
                            $count = 0;
                            $records->each(function (MissingChild $record) use (&$count) {
                                if ($record->status !== MissingChild::STATUS_PENDING)
                                    return;
                                $record->forceFill([
                                    'status' => MissingChild::STATUS_APPROVED,
                                    'approved_at' => now(),
                                    'approved_by' => Auth::id(),
                                ])->save();
                                PostStatusChanged::dispatch($record->refresh());
                                $count++;
                            });
                            Cache::forget('badge_missing_pending');
                            Notification::make()->title("Đã duyệt {$count} hồ sơ")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_reject')
                        ->requiresConfirmation()
                        ->label('Từ chối hàng loạt')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->schema([
                            Textarea::make('rejection_reason')
                                ->label('Lý do từ chối')
                                ->required()
                                ->rows(3)
                                ->placeholder('Nhập lý do áp dụng cho tất cả hồ sơ được chọn...'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $count = 0;
                            $records->each(function (MissingChild $record) use ($data, &$count) {
                                if ($record->status !== MissingChild::STATUS_PENDING)
                                    return;
                                // Cascade revert (quyết định #1) chung 1 transaction: trả
                                // FoundChild liên quan về trạng thái trước và bác bỏ kết quả
                                // đối chiếu CONFIRMED, tránh "Đã tìm thấy" giả.
                                DB::transaction(function () use ($record, $data) {
                                    $record->forceFill([
                                        'status' => MissingChild::STATUS_REJECTED,
                                        'rejection_reason' => $data['rejection_reason'],
                                    ])->save();

                                    app(MissingChildCascadeService::class)->handleRemoval($record, includePending: false);
                                });
                                PostStatusChanged::dispatch($record->refresh());
                                $count++;
                            });
                            Cache::forget('badge_missing_pending');
                            Notification::make()->title("Đã từ chối {$count} hồ sơ")->danger()->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->label('Xóa')
                        ->action(function (Collection $records) {
                            // Cascade revert (quyết định #1) + khử FaceMatchResult PENDING treo
                            // (quyết định #2) trước khi soft-delete, chung 1 transaction.
                            foreach ($records as $record) {
                                DB::transaction(function () use ($record) {
                                    app(MissingChildCascadeService::class)->handleRemoval($record, includePending: true);
                                    $record->delete();
                                });
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
