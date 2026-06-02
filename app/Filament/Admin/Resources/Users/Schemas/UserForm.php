<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin cơ bản')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('full_name')
                                ->label('Họ và tên')
                                ->required(),

                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('phone')
                                ->label('Số điện thoại')
                                ->tel(),

                            TextInput::make('avatar_url')
                                ->label('Ảnh đại diện (URL)')
                                ->url(),
                        ]),

                        TextInput::make('password')
                            ->label('Mật khẩu')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText('Để trống nếu không muốn đổi mật khẩu'),
                    ]),

                Section::make('Phân quyền & Trạng thái')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('role')
                                ->label('Vai trò')
                                ->options([
                                    'ADMIN' => 'Quản trị viên',
                                    'MODERATOR' => 'Kiểm duyệt viên',
                                    'USER' => 'Người dùng',
                                ])
                                ->default('USER')
                                ->required(),

                            Select::make('status')
                                ->label('Trạng thái')
                                ->options([
                                    'ACTIVE' => 'Hoạt động',
                                    'BANNED' => 'Bị khóa',
                                    'PENDING' => 'Chờ kích hoạt',
                                ])
                                ->default('ACTIVE')
                                ->required(),
                        ]),

                        Textarea::make('ban_reason')
                            ->label('Lý do khóa tài khoản')
                            ->placeholder('Điền lý do nếu khóa tài khoản...')
                            ->rows(2),
                    ]),

                Section::make('Cài đặt thông báo')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('province_id')
                                ->label('Tỉnh thành')
                                ->relationship('province', 'name')
                                ->searchable(),

                            TextInput::make('notify_lat')
                                ->label('Vĩ độ thông báo')
                                ->numeric(),

                            TextInput::make('notify_lng')
                                ->label('Kinh độ thông báo')
                                ->numeric(),
                        ]),

                        TextInput::make('notify_radius_km')
                            ->label('Bán kính thông báo (km)')
                            ->numeric()
                            ->default(50),
                    ])
                    ->collapsed(),
            ]);
    }
}