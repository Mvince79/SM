<?php

namespace App\Filament\Admin\Resources\SupportTickets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin ticket')
                    ->schema([
                        Select::make('user_id')
                            ->label('Người gửi')
                            ->relationship('user', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('category')
                            ->label('Danh mục')
                            ->options([
                                'BUG' => 'Báo lỗi',
                                'COMPLAINT' => 'Khiếu nại',
                                'FEEDBACK' => 'Góp ý',
                                'FRAUD_SUSPECT' => 'Nghi ngờ lừa đảo',
                            ])
                            ->required(),

                        TextInput::make('subject')
                            ->label('Tiêu đề')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Nội dung')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Xử lý ticket')
                    ->schema([
                        Select::make('priority')
                            ->label('Mức độ ưu tiên')
                            ->options([
                                'LOW' => 'Thấp',
                                'MEDIUM' => 'Trung bình',
                                'HIGH' => 'Cao',
                            ])
                            ->default('MEDIUM')
                            ->required(),

                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'OPEN' => 'Đang mở',
                                'IN_PROGRESS' => 'Đang xử lý',
                                'RESOLVED' => 'Đã giải quyết',
                                'CLOSED' => 'Đã đóng',
                            ])
                            ->default('OPEN')
                            ->required(),

                        Select::make('assigned_admin_id')
                            ->label('Admin phụ trách')
                            ->relationship(
                                name: 'assignedAdmin',
                                titleAttribute: 'full_name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('role', 'ADMIN'),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('Chưa phân công'),
                    ])
                    ->columns(3),
            ]);
    }
}