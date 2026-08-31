<?php

namespace App\Filament\Admin\Resources\FaceMatchResults\Pages;

use App\Filament\Admin\Resources\FaceMatchResults\FaceMatchResultResource;
use App\Models\FaceMatchResult;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ViewFaceMatchResult extends ViewRecord
{
    protected static string $resource = FaceMatchResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Xác nhận khớp ───────────────────────────────────────────
            Action::make('confirm')
                ->label('Xác nhận khớp')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'PENDING')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận kết quả AI match?')
                ->modalDescription(
                    fn (FaceMatchResult $r): string => 'Độ khớp '.number_format($r->score * 100, 2).'% ('.
                        $r->confidence_label.') cho hồ sơ '.($r->missingChild?->full_name ?? '?').
                        '. Hệ thống sẽ đánh dấu trẻ mất tích là đã tìm thấy và hồ sơ tìm thấy là đã khớp.'
                )
                ->modalSubmitActionLabel('Đồng ý, xác nhận')
                ->action(function (FaceMatchResult $record): void {
                    DB::transaction(function () use ($record) {
                        $record->update([
                            'status' => 'CONFIRMED',
                            'confirmed_by' => Auth::id(),
                            'confirmed_at' => now(),
                        ]);

                        // missingChild bị guard status → forceFill (update() sẽ bỏ qua)
                        $record->missingChild?->forceFill([
                            'status' => 'FOUND',
                            'found_at' => now(),
                        ])->save();

                        $record->foundChild?->update([
                            'status' => 'MATCHED',
                        ]);

                        FaceMatchResult::where('missing_id', $record->missing_id)
                            ->where('id', '!=', $record->id)
                            ->where('status', 'PENDING')
                            ->update([
                                'status' => 'DISMISSED',
                            ]);

                        activity('admin')
                            ->causedBy(Auth::id())
                            ->performedOn($record)
                            ->withProperties([
                                'missing_id' => $record->missing_id,
                                'found_id' => $record->found_id,
                            ])
                            ->log('Xác nhận khớp khuôn mặt bằng AI');
                    });

                    Cache::forget('badge_face_match_pending');

                    Notification::make()
                        ->title('Đã xác nhận kết quả đối chiếu')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            // ── Bác bỏ khớp ─────────────────────────────────────────────
            Action::make('dismiss')
                ->label('Bác bỏ')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'PENDING')
                ->requiresConfirmation()
                ->modalHeading('Bác bỏ kết quả AI match?')
                ->modalDescription('Kết quả này sẽ được đánh dấu là không chính xác. Job AI chạy lại sẽ không tự mở lại kết quả đã bác bỏ.')
                ->modalSubmitActionLabel('Đồng ý, bác bỏ')
                ->action(function (FaceMatchResult $record): void {
                    $record->update([
                        'status' => 'DISMISSED',
                    ]);

                    Cache::forget('badge_face_match_pending');

                    Notification::make()
                        ->title('Đã bác bỏ kết quả đối chiếu')
                        ->danger()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),

            // ── Ca chờ tiếp theo ────────────────────────────────────────
            Action::make('next_pending')
                ->label('Ca chờ tiếp theo')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('gray')
                ->tooltip('Chuyển sang kết quả đang chờ xử lý gần nhất')
                ->visible(fn () => $this->record->status === 'PENDING')
                ->action(function () {
                    $next = FaceMatchResult::query()
                        ->where('status', 'PENDING')
                        ->where('id', '!=', $this->record->id)
                        ->orderBy('id')
                        ->first();

                    if (! $next) {
                        Notification::make()
                            ->title('Không còn kết quả nào đang chờ xử lý')
                            ->info()
                            ->send();

                        return;
                    }

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $next]));
                }),
        ];
    }
}
