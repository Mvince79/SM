<?php

namespace App\Filament\Admin\Resources\KycVerifications\Schemas;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\KycVerification;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KycVerificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── Tổng quan ──────────────────────────────────────────────
                Section::make('Tổng quan')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('user.full_name')
                                    ->label('Người dùng')
                                    ->weight('bold')
                                    ->color('gray')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(
                                        fn ($state, KycVerification $r) => $r->user
                                            ? UserResource::getUrl('view', ['record' => $r->user])
                                            : null
                                    )
                                    ->openUrlInNewTab()
                                    ->tooltip('Xem chi tiết người dùng'),

                                TextEntry::make('status')
                                    ->label('Trạng thái')
                                    ->badge()
                                    ->icon(fn (string $state): string => match ($state) {
                                        'VERIFIED' => 'heroicon-o-check-circle',
                                        'REJECTED' => 'heroicon-o-x-circle',
                                        default => 'heroicon-o-clock',
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'VERIFIED' => 'success',
                                        'REJECTED' => 'danger',
                                        default => 'warning',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'PENDING' => 'Chờ duyệt',
                                        'VERIFIED' => 'Đã xác thực',
                                        'REJECTED' => 'Từ chối',
                                        default => $state,
                                    }),

                                TextEntry::make('created_at')
                                    ->label('Ngày gửi')
                                    ->dateTime('d/m/Y H:i')
                                    ->since()
                                    ->tooltip(
                                        fn ($state, KycVerification $r): string => $r->created_at?->format('d/m/Y H:i:s') ?? ''
                                    ),
                            ]),
                    ]),

                // ── Bằng chứng định danh ──────────────────────────────────
                Section::make('Bằng chứng định danh')
                    ->description('Ảnh chụp giấy tờ và ảnh tự chụp (selfie) của người dùng. Nhấn vào ảnh để phóng to.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                ImageEntry::make('front_image_path')
                                    ->label('CCCD mặt trước')
                                    ->getStateUsing(fn (KycVerification $record): string => route(
                                        'admin.kyc-verifications.image',
                                        ['kycVerification' => $record, 'side' => 'front'],
                                    ))
                                    ->height(260)
                                    ->extraImgAttributes([
                                        'style' => 'width:100%;height:260px;object-fit:cover;border-radius:12px;cursor:zoom-in;',
                                    ])
                                    ->openUrlInNewTab(),

                                ImageEntry::make('back_image_path')
                                    ->label('CCCD mặt sau')
                                    ->getStateUsing(fn (KycVerification $record): string => route(
                                        'admin.kyc-verifications.image',
                                        ['kycVerification' => $record, 'side' => 'back'],
                                    ))
                                    ->height(260)
                                    ->extraImgAttributes([
                                        'style' => 'width:100%;height:260px;object-fit:cover;border-radius:12px;cursor:zoom-in;',
                                    ])
                                    ->openUrlInNewTab(),

                                ImageEntry::make('selfie_path')
                                    ->label('Ảnh tự chụp (selfie)')
                                    ->getStateUsing(fn (KycVerification $record): string => route(
                                        'admin.kyc-verifications.image',
                                        ['kycVerification' => $record, 'side' => 'selfie'],
                                    ))
                                    ->height(260)
                                    ->extraImgAttributes([
                                        'style' => 'width:100%;height:260px;object-fit:cover;border-radius:12px;cursor:zoom-in;',
                                    ])
                                    ->openUrlInNewTab(),
                            ]),
                    ])
                    ->columnSpanFull(),

                // ── Đối chiếu định danh (OCR ↔ Khai báo) ──────────────────
                Section::make('Đối chiếu định danh (OCR ↔ Khai báo)')
                    ->description('So sánh thông tin trích xuất từ giấy tờ (OCR) với thông tin người dùng đã khai báo. Mục nào lệch sẽ được bôi nổi bật.')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('full_name_on_id')
                                    ->label('Họ tên trên CCCD (OCR)')
                                    ->copyable()
                                    ->placeholder('Không có'),

                                TextEntry::make('user.full_name')
                                    ->label('Họ tên khai báo')
                                    ->color('gray')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(
                                        fn ($state, KycVerification $r) => $r->user
                                            ? UserResource::getUrl('view', ['record' => $r->user])
                                            : null
                                    )
                                    ->openUrlInNewTab()
                                    ->tooltip('Xem chi tiết người dùng'),

                                TextEntry::make('decrypted_id_number')
                                    ->label('Số CCCD')
                                    ->copyable()
                                    ->placeholder('Không có'),

                                TextEntry::make('dob_on_id')
                                    ->label('Ngày sinh trên CCCD')
                                    ->date('d/m/Y')
                                    ->placeholder('Không có'),

                                TextEntry::make('id_type')
                                    ->label('Loại giấy tờ')
                                    ->badge()
                                    ->color('info')
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'CMND_9' => 'CMND 9 số',
                                        'CCCD_12' => 'CCCD 12 số',
                                        default => $state ?: 'Chưa xác định',
                                    }),

                                TextEntry::make('ocr_provider')
                                    ->label('Trích xuất bởi (OCR)')
                                    ->badge()
                                    ->placeholder('Chưa có')
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'fpt_ai' => 'FPT.AI',
                                        'vnpt_ekyc' => 'VNPT eKYC',
                                        'tesseract' => 'Tesseract',
                                        default => $state ?: '—',
                                    }),

                                TextEntry::make('name_check')
                                    ->label('Đối chiếu họ tên')
                                    ->badge()
                                    ->getStateUsing(
                                        fn (KycVerification $record): string => self::nameMismatch($record) ? 'Không khớp' : 'Khớp'
                                    )
                                    ->color(fn (string $state): string => $state === 'Khớp' ? 'success' : 'danger')
                                    ->icon(fn (string $state): string => $state === 'Khớp'
                                        ? 'heroicon-o-check-circle'
                                        : 'heroicon-o-exclamation-triangle'),

                                TextEntry::make('face_match_level')
                                    ->label('Mức độ tin cậy khuôn mặt')
                                    ->badge()
                                    ->getStateUsing(
                                        fn (KycVerification $record): string => self::scoreLabel((float) $record->face_match_score)
                                    )
                                    ->color(fn (string $state): string => match ($state) {
                                        'Tin cậy cao' => 'success',
                                        'Cần kiểm tra' => 'warning',
                                        default => 'danger',
                                    }),
                            ]),
                    ]),

                // ── Đối chiếu khuôn mặt (AI) ──────────────────────────────
                Section::make('Đối chiếu khuôn mặt (AI)')
                    ->description(
                        'So sánh ảnh selfie với ảnh chân dung trên CCCD. '.
                        'Ngưỡng tham chiếu: ≥ '.
                        round((float) config('face_match.high_confidence_threshold', 0.85) * 100).
                        '% = tin cậy cao (xanh); '.
                        round((float) config('face_match.score_threshold', 0.70) * 100).
                        '–'.round((float) config('face_match.high_confidence_threshold', 0.85) * 100).
                        '% = cần kiểm tra (vàng); < '.
                        round((float) config('face_match.score_threshold', 0.70) * 100).
                        '% = thấp (đỏ).'
                    )
                    ->icon('heroicon-o-face-smile')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                ImageEntry::make('selfie_path')
                                    ->label('Selfie')
                                    ->getStateUsing(fn (KycVerification $record): string => route(
                                        'admin.kyc-verifications.image',
                                        ['kycVerification' => $record, 'side' => 'selfie'],
                                    ))
                                    ->height(240)
                                    ->extraImgAttributes([
                                        'style' => 'width:100%;height:240px;object-fit:cover;border-radius:12px;',
                                    ])
                                    ->openUrlInNewTab(),

                                ImageEntry::make('front_image_path')
                                    ->label('Chân dung CCCD')
                                    ->getStateUsing(fn (KycVerification $record): string => route(
                                        'admin.kyc-verifications.image',
                                        ['kycVerification' => $record, 'side' => 'front'],
                                    ))
                                    ->height(240)
                                    ->extraImgAttributes([
                                        'style' => 'width:100%;height:240px;object-fit:cover;border-radius:12px;',
                                    ])
                                    ->openUrlInNewTab(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('face_match_score')
                                    ->label('Điểm khớp khuôn mặt (AI)')
                                    ->badge()
                                    ->size('lg')
                                    ->color(
                                        fn ($state): string => $state === null ? 'gray' : self::scoreColor((float) $state)
                                    )
                                    ->formatStateUsing(
                                        fn ($state): string => $state !== null
                                                ? number_format((float) $state * 100, 2).'%'
                                                : 'Chưa có'
                                    ),

                                TextEntry::make('face_match_source')
                                    ->label('Đánh giá')
                                    ->getStateUsing(
                                        fn (KycVerification $record): string => $record->face_match_score === null
                                                ? 'Chưa có dữ liệu đối chiếu'
                                                : 'Xem mức độ tin cậy bên trên'
                                    )
                                    ->color('gray'),
                            ]),
                    ]),

                // ── Lịch sử duyệt ─────────────────────────────────────────
                Section::make('Lịch sử duyệt')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('reviewedBy.full_name')
                                    ->label('Người duyệt')
                                    ->placeholder('Chưa có'),

                                TextEntry::make('verified_at')
                                    ->label('Thời gian duyệt')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('Chưa duyệt'),

                                TextEntry::make('rejected_reason')
                                    ->label('Lý do từ chối')
                                    ->color('danger')
                                    ->columnSpanFull()
                                    ->placeholder('—'),
                            ]),
                    ]),
            ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    protected static function nameMismatch(KycVerification $r): bool
    {
        if (! filled($r->full_name_on_id) || ! $r->user) {
            return false;
        }

        return mb_strtolower(trim($r->full_name_on_id)) !== mb_strtolower(trim($r->user->full_name));
    }

    protected static function scoreColor(float $score): string
    {
        if ($score >= (float) config('face_match.high_confidence_threshold', 0.85)) {
            return 'success';
        }
        if ($score >= (float) config('face_match.score_threshold', 0.70)) {
            return 'warning';
        }

        return 'danger';
    }

    protected static function scoreLabel(float $score): string
    {
        return match (self::scoreColor($score)) {
            'success' => 'Tin cậy cao',
            'warning' => 'Cần kiểm tra',
            default => 'Thấp',
        };
    }
}
