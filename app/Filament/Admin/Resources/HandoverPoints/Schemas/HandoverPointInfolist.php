<?php

namespace App\Filament\Admin\Resources\HandoverPoints\Schemas;

use App\Models\HandoverPoint;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;

class HandoverPointInfolist
{
    public static function typeLabel(?string $state): string
    {
        return match ($state) {
            'POLICE_STATION' => 'Công an',
            'COMMUNE_PEOPLE_COMMITTEE' => 'UBND xã/phường',
            'HOTLINE_111' => 'Tổng đài Quốc gia 111',
            'NGO' => 'Tổ chức NGO',
            'OTHER' => 'Đơn vị khác',
            default => $state ?? '—',
        };
    }

    public static function typeColor(?string $state): string
    {
        return match ($state) {
            'POLICE_STATION' => 'danger',
            'COMMUNE_PEOPLE_COMMITTEE' => 'info',
            'HOTLINE_111' => 'success',
            'NGO' => 'warning',
            'OTHER' => 'gray',
            default => 'gray',
        };
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ══════════════════════════════════════════════════════════════
            // VĂN BẢN XÁC NHẬN THẨM QUYỀN — ảnh preview to, PDF hiện link tải
            // ══════════════════════════════════════════════════════════════
            Section::make('Văn bản xác nhận thẩm quyền')
                ->icon('heroicon-o-document-check')
                ->schema([
                    ImageEntry::make('verification_document_path')
                        ->hiddenLabel()
                        ->disk('public')
                        ->imageHeight(300)   
                        ->extraImgAttributes(['class' => 'rounded-xl object-contain'])
                        ->visible(
                            fn(?HandoverPoint $record): bool =>
                            filled($record?->verification_document_path)
                            && !str_ends_with(strtolower($record->verification_document_path), '.pdf')
                        )
                        ->columnSpanFull(),

                    TextEntry::make('verification_document_pdf')
                        ->hiddenLabel()
                        ->state('Tải xuống văn bản (PDF)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->url(
                            fn(?HandoverPoint $record): ?string =>
                            $record?->verification_document_path
                            ? Storage::disk('public')->url($record->verification_document_path)
                            : null
                        )
                        ->openUrlInNewTab()
                        ->visible(
                            fn(?HandoverPoint $record): bool =>
                            filled($record?->verification_document_path)
                            && str_ends_with(strtolower($record->verification_document_path), '.pdf')
                        ),

                    TextEntry::make('no_document')
                        ->hiddenLabel()
                        ->state('Chưa có văn bản xác nhận')
                        ->color('gray')
                        ->visible(fn(?HandoverPoint $record): bool => blank($record?->verification_document_path)),
                ])
                ->columnSpanFull(),

            // ══════════════════════════════════════════════════════════════
            // THÔNG TIN ĐIỂM BÀN GIAO
            // ══════════════════════════════════════════════════════════════
            Section::make('Thông tin điểm bàn giao')
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Flex::make([
                        TextEntry::make('name')
                            ->label('Tên điểm bàn giao')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('type')
                            ->label('Loại')
                            ->badge()
                            ->alignEnd()
                            ->grow(false)
                            ->formatStateUsing(fn(?string $state) => self::typeLabel($state))
                            ->color(fn(?string $state) => self::typeColor($state)),
                    ])->from('sm'),

                    Grid::make(2)->schema([
                        TextEntry::make('province.name')
                            ->label('Tỉnh/Thành phố')
                            ->placeholder('—'),

                        TextEntry::make('responsible_org_name')
                            ->label('Đơn vị chịu trách nhiệm')
                            ->placeholder('—'),
                    ]),

                    TextEntry::make('address')
                        ->label('Địa chỉ')
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextEntry::make('hotline_phone')
                            ->label('Số điện thoại/Hotline')
                            ->icon('heroicon-o-phone')
                            ->iconColor('success')
                            ->copyable()
                            ->copyMessage('Đã sao chép số điện thoại!')
                            ->placeholder('—'),

                        TextEntry::make('legal_authority_note')
                            ->label('Căn cứ/Xác nhận thẩm quyền')
                            ->placeholder('Không áp dụng (Tổng đài 111)'),
                    ]),
                ]),

            // ══════════════════════════════════════════════════════════════
            // VỊ TRÍ TRÊN BẢN ĐỒ
            // ══════════════════════════════════════════════════════════════
            Section::make('Vị trí trên bản đồ')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('lat')
                            ->label('Vĩ độ')
                            ->fontFamily(FontFamily::Mono),

                        TextEntry::make('lng')
                            ->label('Kinh độ')
                            ->fontFamily(FontFamily::Mono),

                        TextEntry::make('map_link')
                            ->label('Bản đồ')
                            ->state('Mở Google Maps')
                            ->icon('heroicon-o-map')
                            ->color('info')
                            ->url(
                                fn(HandoverPoint $record): ?string =>
                                $record->lat && $record->lng
                                ? "https://www.google.com/maps/search/?api=1&query={$record->lat},{$record->lng}"
                                : null
                            )
                            ->openUrlInNewTab(),
                    ]),
                ]),

            // ══════════════════════════════════════════════════════════════
            // THIẾT LẬP HOẠT ĐỘNG
            // ══════════════════════════════════════════════════════════════
            Section::make('Thiết lập hoạt động')
                ->icon('heroicon-o-adjustments-horizontal')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('priority_order')
                            ->label('Thứ tự ưu tiên')
                            ->numeric(),

                        IconEntry::make('is_active')
                            ->label('Đang hoạt động')
                            ->boolean(),
                    ]),
                ]),

        ]);
    }
}
