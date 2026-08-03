<?php

namespace App\Filament\Admin\Resources\SupportTickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class SupportTicketInfolist
{
    public static function priorityColor(?string $state): string
    {
        return match ($state) {
            'LOW' => 'gray',
            'MEDIUM' => 'warning',
            'HIGH' => 'danger',
            default => 'gray',
        };
    }

    public static function statusColor(?string $state): string
    {
        return match ($state) {
            'OPEN' => 'warning',
            'IN_PROGRESS' => 'info',
            'RESOLVED' => 'success',
            'CLOSED' => 'gray',
            default => 'gray',
        };
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ══════════════════════════════════════════════════════════════
            // THÔNG TIN TICKET
            // ══════════════════════════════════════════════════════════════
            Section::make('Thông tin ticket')
                ->icon('heroicon-o-lifebuoy')
                ->schema([
                    Flex::make([
                        TextEntry::make('subject')
                            ->label('Tiêu đề')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('status')
                            ->label('Trạng thái')
                            ->badge()
                            ->alignEnd()
                            ->grow(false)
                            ->formatStateUsing(fn(?string $state): string => match ($state) {
                                'OPEN' => 'Đang mở',
                                'IN_PROGRESS' => 'Đang xử lý',
                                'RESOLVED' => 'Đã giải quyết',
                                'CLOSED' => 'Đã đóng',
                                default => $state ?? '—',
                            })
                            ->color(fn(?string $state) => self::statusColor($state)),
                    ])->from('sm'),

                    Grid::make(4)->schema([
                        TextEntry::make('id')
                            ->label('Mã ticket')
                            ->prefix('#')
                            ->copyable(),

                        TextEntry::make('user.full_name')
                            ->label('Người gửi')
                            ->placeholder('Không xác định'),

                        TextEntry::make('category')
                            ->label('Danh mục')
                            ->badge()
                            ->formatStateUsing(fn(?string $state): string => match ($state) {
                                'BUG' => 'Báo lỗi',
                                'COMPLAINT' => 'Khiếu nại',
                                'FEEDBACK' => 'Góp ý',
                                'FRAUD_SUSPECT' => 'Nghi ngờ lừa đảo',
                                default => $state ?? '—',
                            })
                            ->color(fn(?string $state): string => match ($state) {
                                'BUG' => 'danger',
                                'COMPLAINT' => 'warning',
                                'FEEDBACK' => 'info',
                                'FRAUD_SUSPECT' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('priority')
                            ->label('Mức độ ưu tiên')
                            ->badge()
                            ->formatStateUsing(fn(?string $state): string => match ($state) {
                                'LOW' => 'Thấp',
                                'MEDIUM' => 'Trung bình',
                                'HIGH' => 'Cao',
                                default => $state ?? '—',
                            })
                            ->color(fn(?string $state) => self::priorityColor($state)),
                    ]),

                    TextEntry::make('description')
                        ->label('Nội dung')
                        ->columnSpanFull(),
                ]),

            // ══════════════════════════════════════════════════════════════
            // XỬ LÝ TICKET
            // ══════════════════════════════════════════════════════════════
            Section::make('Xử lý ticket')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('assignedAdmin.full_name')
                            ->label('Admin phụ trách')
                            ->placeholder('Chưa phân công')
                            ->badge(fn($state) => is_null($state))
                            ->color(fn($state) => is_null($state) ? 'gray' : 'success'),

                        TextEntry::make('created_at')
                            ->label('Ngày tạo')
                            ->dateTime('H:i d/m/Y'),

                        TextEntry::make('updated_at')
                            ->label('Cập nhật cuối')
                            ->dateTime('H:i d/m/Y'),
                    ]),
                ]),

        ]);
    }
}
