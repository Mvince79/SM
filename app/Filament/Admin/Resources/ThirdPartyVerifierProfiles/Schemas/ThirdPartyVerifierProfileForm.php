<?php

namespace App\Filament\Admin\Resources\ThirdPartyVerifierProfiles\Schemas;

use App\Models\HandoverPoint;
use App\Models\ThirdPartyVerifierProfile;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ThirdPartyVerifierProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin tài khoản xác minh')
                    ->description(
                        'Gán tài khoản Third-Party Verifier vào một điểm bàn giao an toàn.'
                    )
                    ->schema([
                        Select::make('user_id')
                            ->label('Tài khoản Verifier')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'full_name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('role', 'THIRD_PARTY_VERIFIER')
                                    ->orderBy('full_name'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (User $record): string => sprintf(
                                    '%s (%s)',
                                    $record->full_name ?: 'Chưa có tên',
                                    $record->email,
                                )
                            )
                            ->searchable(['full_name', 'email'])
                            ->preload()
                            ->unique(
                                table: ThirdPartyVerifierProfile::class,
                                column: 'user_id',
                                ignoreRecord: true,
                            )
                            ->disabled(
                                fn (?ThirdPartyVerifierProfile $record): bool => $record !== null
                            )
                            ->helperText(
                                'Chỉ hiển thị tài khoản có role THIRD_PARTY_VERIFIER. Mỗi tài khoản chỉ có một hồ sơ.'
                            )
                            ->required(),

                        Select::make('handover_point_id')
                            ->label('Điểm bàn giao phụ trách')
                            ->relationship(
                                name: 'handoverPoint',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_active', true)
                                    ->orderBy('priority_order')
                                    ->orderBy('name'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (HandoverPoint $record): string => $record->address
                                    ? "{$record->name} — {$record->address}"
                                    : $record->name
                            )
                            ->searchable(['name', 'address'])
                            ->preload()
                            ->helperText('Admin có thể thay đổi điểm bàn giao phụ trách sau này.')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Thông tin đơn vị')
                    ->schema([
                        Select::make('organization_type')
                            ->label('Loại tổ chức')
                            ->options([
                                'POLICE' => 'Công an',
                                'GOVERNMENT' => 'Cơ quan nhà nước',
                                'NGO' => 'Tổ chức phi chính phủ (NGO)',
                            ])
                            ->native(false)
                            ->required(),

                        TextInput::make('organization_name')
                            ->label('Tên tổ chức/đơn vị')
                            ->placeholder('Ví dụ: Công an Phường An Khánh')
                            ->maxLength(255)
                            ->required(),

                        TextInput::make('badge_or_staff_id')
                            ->label('Số hiệu hoặc mã nhân viên')
                            ->placeholder('Ví dụ: CA-012345')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}