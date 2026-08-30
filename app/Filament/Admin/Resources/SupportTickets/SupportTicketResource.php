<?php

namespace App\Filament\Admin\Resources\SupportTickets;

use App\Filament\Admin\Resources\SupportTickets\RelationManagers\RepliesRelationManager;
use App\Filament\Admin\Resources\SupportTickets\Pages\CreateSupportTicket;
use App\Filament\Admin\Resources\SupportTickets\Pages\EditSupportTicket;
use App\Filament\Admin\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Admin\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Admin\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Filament\Admin\Resources\SupportTickets\Schemas\SupportTicketInfolist;
use App\Filament\Admin\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use BackedEnum;
use Illuminate\Support\Facades\Cache;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;
    protected static ?string $navigationLabel = 'Yêu Cầu Hỗ Trợ';
    protected static ?string $modelLabel = 'Yêu cầu hỗ trợ';
    protected static ?string $pluralModelLabel = 'Yêu cầu hỗ trợ';


    public static function form(Schema $schema): Schema
    {
        return SupportTicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupportTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RepliesRelationManager::class,
        ];
    }

    /**
     * Badge đếm ticket đang mở (OPEN/IN_PROGRESS) trên sidebar admin — P0.
     * Copy pattern Item 10 (FoundChildResource::getNavigationBadge).
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Cache::remember('badge_support_tickets_open', 300, function () {
            return SupportTicket::open()->count();
        });

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = (int) Cache::get('badge_support_tickets_open', 0);

        return $count > 10 ? 'danger' : 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'create' => CreateSupportTicket::route('/create'),
            'view' => ViewSupportTicket::route('/{record}'),
            'edit' => EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
