<?php

namespace App\Filament\Admin\Resources\KycVerifications\Schemas;

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

                Section::make('Ảnh xác thực')
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
                                    ->label('Ảnh Selfie')
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

                Section::make('Thông tin KYC')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextEntry::make('user.full_name')
                                    ->label('Người dùng'),

                                TextEntry::make('user.email')
                                    ->label('Email'),

                                TextEntry::make('decrypted_id_number')
                                    ->label('Số CCCD')
                                    ->copyable()
                                    ->placeholder('Không có'),

                                TextEntry::make('full_name_on_id')
                                    ->label('Họ tên trên CCCD'),

                                TextEntry::make('dob_on_id')
                                    ->label('Ngày sinh'),

                                TextEntry::make('ocr_provider')
                                    ->label('OCR Provider')
                                    ->badge(),

                                TextEntry::make('face_match_score')
                                    ->label('Face Match')
                                    ->formatStateUsing(
                                        fn($state) => number_format((float) $state * 100, 2) . '%'
                                    )
                                    ->badge(),

                                TextEntry::make('status')
                                    ->label('Trạng thái')
                                    ->badge(),

                                TextEntry::make('verified_at')
                                    ->label('Ngày duyệt')
                                    ->dateTime('d/m/Y H:i'),

                                TextEntry::make('rejected_reason')
                                    ->label('Lý do từ chối')
                                    ->columnSpanFull(),

                            ]),

                    ]),

            ]);
    }
}
