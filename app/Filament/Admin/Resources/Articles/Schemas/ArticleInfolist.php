<?php

namespace App\Filament\Admin\Resources\Articles\Schemas;

use App\Models\Article;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ══════════════════════════════════════════════════════════════
            // HERO COVER IMAGE — ảnh bìa to ở đầu trang (đúng tinh thần R-01)
            // ══════════════════════════════════════════════════════════════
            Section::make()
                ->schema([
                    ImageEntry::make('cover_image_path')
                        ->hiddenLabel()
                        ->disk('public')
                        ->imageHeight(380)   
                        ->extraImgAttributes([
                            'class' => 'w-full object-cover rounded-2xl',
                        ])
                        ->placeholder('Chưa có ảnh bìa')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            // ══════════════════════════════════════════════════════════════
            // NỘI DUNG BÀI VIẾT
            // ══════════════════════════════════════════════════════════════
            Section::make('Nội dung bài viết')
                ->icon('heroicon-o-newspaper')
                ->schema([
                    Flex::make([
                        TextEntry::make('title')
                            ->label('Tiêu đề')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('published_at')
                            ->label('Trạng thái')
                            ->badge()
                            ->alignEnd()
                            ->grow(false)
                            ->state(fn(Article $record): string => $record->published_at ? 'published' : 'draft')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'published' => 'Đã xuất bản',
                                default => 'Bản nháp',
                            })
                            ->color(fn(string $state): string => match ($state) {
                                'published' => 'success',
                                default => 'gray',
                            }),
                    ])->from('sm'),

                    TextEntry::make('slug')
                        ->label('Đường dẫn')
                        ->prefix('/bai-viet/')
                        ->copyable()
                        ->copyMessage('Đã sao chép đường dẫn!')
                        ->color('info'),

                    TextEntry::make('content')
                        ->label('Nội dung')
                        ->html()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ══════════════════════════════════════════════════════════════
            // SEO — tái dùng đúng nội dung preview đã có trong ArticleForm
            // ══════════════════════════════════════════════════════════════
            Section::make('SEO')
                ->icon('heroicon-o-magnifying-glass')
                ->description('Thông tin hiển thị trên công cụ tìm kiếm')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('meta_title')
                            ->label('SEO title')
                            ->placeholder('Chưa đặt (dùng tiêu đề bài viết)'),

                        TextEntry::make('meta_description')
                            ->label('SEO description')
                            ->placeholder('Chưa đặt'),
                    ]),

                    TextEntry::make('seo_preview')
                        ->hiddenLabel()
                        ->state(function (Article $record): HtmlString {
                            $title = $record->meta_title ?: $record->title ?: 'Tiêu đề bài viết';
                            $slug = $record->slug ?: 'duong-dan-bai-viet';
                            $description = $record->meta_description
                                ?: 'Mô tả bài viết sẽ xuất hiện tại đây khi người dùng tìm kiếm trên Google.';
                            $url = url('/bai-viet/' . $slug);

                            return new HtmlString(
                                '<div style="max-width:650px;padding:16px;border:1px solid #e5e7eb;border-radius:8px;background:#ffffff;">'
                                . '<div style="color:#202124;font-size:14px;margin-bottom:4px;word-break:break-all;">' . e($url) . '</div>'
                                . '<div style="color:#1a0dab;font-size:20px;line-height:1.3;margin-bottom:4px;">' . e(Str::limit($title, 60, '...')) . '</div>'
                                . '<div style="color:#4d5156;font-size:14px;line-height:1.5;">' . e(Str::limit($description, 160, '...')) . '</div>'
                                . '</div>'
                            );
                        })
                        ->html()
                        ->columnSpanFull(),
                ]),

            // ══════════════════════════════════════════════════════════════
            // TÁC GIẢ & XUẤT BẢN
            // ══════════════════════════════════════════════════════════════
            Section::make('Tác giả & Xuất bản')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('author.full_name')
                            ->label('Tác giả')
                            ->placeholder('—'),

                        TextEntry::make('published_at')
                            ->label('Thời gian xuất bản')
                            ->dateTime('H:i d/m/Y')
                            ->placeholder('Chưa xuất bản (bản nháp)')
                            ->badge(fn(Article $record): bool => is_null($record->published_at))
                            ->formatStateUsing(fn($state, Article $record) => $record->published_at
                                ? $record->published_at->format('H:i d/m/Y')
                                : 'Bản nháp')
                            ->color(fn(Article $record) => $record->published_at ? 'success' : 'warning'),

                        TextEntry::make('created_at')
                            ->label('Ngày tạo')
                            ->dateTime('H:i d/m/Y'),
                    ]),
                ]),

        ]);
    }
}
